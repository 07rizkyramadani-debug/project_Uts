<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Services\LoanService;
use Modules\Membership\Models\Member;

beforeEach(function () {
    $this->account = User::factory()->create(['role' => 'user']);
    $this->member = Member::factory()->create(['user_id' => $this->account->id]);
    $this->service = app(LoanService::class);
});

it('tamu diarahkan ke login, admin dan pustakawan ditolak', function () {
    $this->get('/my-loans')->assertRedirect('/login');

    foreach (['admin', 'pustakawan'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->get('/my-loans')->assertForbidden();
    }
});

it('user melihat pinjaman sendiri lengkap dengan jatuh tempo, tetapi tidak milik anggota lain', function () {
    $mine = Book::factory()->create(['stock' => 3, 'total_stock' => 3, 'title' => 'Buku Milik Saya']);
    $theirs = Book::factory()->create(['stock' => 3, 'total_stock' => 3, 'title' => 'Buku Orang Lain']);
    $other = Member::factory()->create();

    $loan = $this->service->borrow($this->member->id, [['book_id' => $mine->id]]);
    $this->service->borrow($other->id, [['book_id' => $theirs->id]]);

    $this->actingAs($this->account)->get('/my-loans')
        ->assertOk()
        ->assertSee('Buku Milik Saya')
        ->assertSee($loan->loan_number)
        ->assertSee($loan->borrowed_at->format('d M Y'))
        ->assertSee($loan->due_at->format('d M Y'))
        ->assertDontSee('Buku Orang Lain');
});

it('menampilkan denda berjalan untuk pinjaman terlambat dan denda final untuk yang sudah kembali', function () {
    $late = Book::factory()->create(['stock' => 3, 'total_stock' => 3]);
    $done = Book::factory()->create(['stock' => 3, 'total_stock' => 3]);

    $lateLoan = $this->service->borrow($this->member->id, [['book_id' => $late->id, 'quantity' => 2]]);
    $lateLoan->update(['due_at' => now()->subDays(3)]);

    $doneLoan = $this->service->borrow($this->member->id, [['book_id' => $done->id]]);
    $doneLoan->update(['due_at' => now()->subDays(2)]);
    $this->service->returnLoan($doneLoan->fresh());

    expect($lateLoan->fresh('details')->accruedFine())->toBe(6000.0)   // 3 hari x Rp1.000 x 2 eksemplar
        ->and($doneLoan->fresh('details')->accruedFine())->toBe(2000.0);

    $this->actingAs($this->account)->get('/my-loans')
        ->assertOk()
        ->assertSee('Rp 6.000')
        ->assertSee('Rp 2.000')
        ->assertSee('Terlambat');
});

it('menampilkan tanggal dikembalikan dan jumlah buku pada pinjaman yang sudah selesai', function () {
    $book = Book::factory()->create(['stock' => 5, 'total_stock' => 5]);
    $loan = $this->service->borrow($this->member->id, [['book_id' => $book->id, 'quantity' => 2]], null, 'Untuk skripsi');
    $returned = $this->service->returnLoan($loan);

    $this->actingAs($this->account)->get('/my-loans')
        ->assertOk()
        ->assertSee($returned->returned_at->format('d M Y'))
        ->assertSee('×2', false)
        ->assertSee('Catatan: Untuk skripsi');
});

it('filter status membatasi daftar', function () {
    $books = Book::factory()->count(2)->create(['stock' => 3, 'total_stock' => 3]);
    $loan = $this->service->borrow($this->member->id, [['book_id' => $books[0]->id]]);
    $this->service->returnLoan($loan);
    $active = $this->service->borrow($this->member->id, [['book_id' => $books[1]->id]]);

    $this->actingAs($this->account)->get('/my-loans?status=returned')
        ->assertOk()->assertSee($loan->loan_number)->assertDontSee($active->loan_number);
});

it('akun user tanpa data anggota otomatis diaktifkan, bukan error', function () {
    $fresh = User::factory()->create(['role' => 'user']);

    $this->actingAs($fresh)->get('/my-loans')->assertOk()->assertDontSee('tidak aktif');

    expect(Member::where('user_id', $fresh->id)->value('status'))->toBe('active');
});

it('anggota yang dihapus admin melihat pesan bantuan, bukan error', function () {
    $this->member->delete();

    $this->actingAs($this->account)->get('/my-loans')
        ->assertOk()->assertSee('Keanggotaan tidak aktif');
});

it('jumlah query tidak bertambah seiring banyaknya pinjaman (tanpa N+1)', function () {
    $books = Book::factory()->count(6)->create(['stock' => 5, 'total_stock' => 5]);
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->account)->get('/my-loans')->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    $this->service->borrow($this->member->id, [['book_id' => $books[0]->id]]);
    $few = $count();

    foreach ([1, 2, 3, 4, 5] as $i) {
        $loan = $this->service->borrow($this->member->id, [['book_id' => $books[$i]->id]]);
        $this->service->returnLoan($loan);
    }

    expect($count())->toBe($few);
});

describe('pengajuan peminjaman', function () {
    beforeEach(function () {
        $this->pustakawan = User::factory()->create(['role' => 'pustakawan']);
        $this->book = Book::factory()->create(['stock' => 2, 'total_stock' => 2]);
        $this->submit = fn (?array $items = null, ?User $as = null) => $this->actingAs($as ?? $this->account)->post('/my-loans', [
            'items' => $items ?? [['book_id' => $this->book->id, 'quantity' => 1]],
            'notes' => 'Untuk tugas akhir',
        ]);
    });

    it('user mengajukan: tersimpan pending tanpa mengubah stok, anggota, maupun jatuh tempo', function () {
        ($this->submit)()->assertSessionHasNoErrors()->assertRedirect('/my-loans');

        $loan = Loan::sole();
        expect($loan->status)->toBe('pending')
            ->and($loan->member_id)->toBe($this->member->id)
            ->and($loan->due_at)->toBeNull()
            ->and($loan->borrowed_at)->toBeNull()
            ->and($loan->details)->toHaveCount(1)
            ->and($loan->details->first()->book_title)->toBe($this->book->title)
            ->and($this->book->fresh()->stock)->toBe(2)
            ->and($this->member->fresh()->active_loans_count)->toBe(0)
            ->and($this->member->fresh()->status)->toBe('active');

        $this->actingAs($this->account)->get('/my-loans')->assertSee('Menunggu persetujuan');
    });

    it('anggota ditentukan dari akun login, bukan dari input yang dikirim', function () {
        $other = Member::factory()->create();

        $this->actingAs($this->account)->post('/my-loans', [
            'member_id' => $other->id,
            'items' => [['book_id' => $this->book->id]],
        ])->assertSessionHasNoErrors();

        expect(Loan::sole()->member_id)->toBe($this->member->id);
    });

    it('divalidasi: item kosong dan jumlah > 5 ditolak, baris kosong diabaikan', function () {
        ($this->submit)([])->assertSessionHasErrors(['items' => 'Pilih minimal satu buku untuk dipinjam.']);
        ($this->submit)([['book_id' => $this->book->id, 'quantity' => 6]])
            ->assertSessionHasErrors(['items.0.quantity' => 'Jumlah per buku maksimal 5 eksemplar.']);
        ($this->submit)([['book_id' => $this->book->id], ['book_id' => '']])->assertSessionHasNoErrors();

        expect(Loan::count())->toBe(1);
    });

    it('ditolak bila stok kurang, anggota ditangguhkan, atau melewati batas termasuk yang menunggu', function () {
        ($this->submit)([['book_id' => $this->book->id, 'quantity' => 3]])->assertSessionHasErrors('stock');

        $banyak = Book::factory()->create(['stock' => 5, 'total_stock' => 5]);
        ($this->submit)([['book_id' => $banyak->id, 'quantity' => 3]])->assertSessionHasNoErrors();
        ($this->submit)([['book_id' => $banyak->id, 'quantity' => 3]])
            ->assertSessionHasErrors(['member' => 'Pengajuan melebihi batas pinjaman (sedang meminjam 0, menunggu persetujuan 3, maksimal 5).']);

        $this->member->update(['status' => 'suspended']);
        ($this->submit)()->assertSessionHasErrors('member');

        expect(Loan::count())->toBe(1);
    });

    it('akun user tanpa data anggota otomatis diaktifkan lalu bisa mengajukan; pustakawan tidak lewat jalur user', function () {
        $baru = User::factory()->create(['role' => 'user']);

        ($this->submit)(null, $baru)->assertSessionHasNoErrors()->assertRedirect('/my-loans');
        expect(Member::where('user_id', $baru->id)->where('status', 'active')->exists())->toBeTrue();
        expect(Loan::count())->toBe(1);

        ($this->submit)(null, $this->pustakawan)->assertForbidden();
        expect(Loan::count())->toBe(1);
    });

    it('user mengajukan langsung dari daftar buku dan halaman detail buku', function () {
        $this->actingAs($this->account)->get('/books')
            ->assertOk()
            ->assertSee('Ajukan Pinjam')
            ->assertSee('name="items[0][book_id]" value="'.$this->book->id.'"', false);

        $this->actingAs($this->account)->get("/books/{$this->book->id}")
            ->assertOk()
            ->assertSee('Ajukan Pinjam');

        $empty = Book::factory()->create(['stock' => 0, 'total_stock' => 1]);
        $this->actingAs($this->account)->get('/books')->assertSee('Stok habis');
    });

    it('tombol ajukan muncul untuk akun user (anggota otomatis) tetapi tidak untuk pustakawan, dan form lama diarahkan ke daftar buku', function () {
        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/books')->assertSee('Ajukan Pinjam');
        $this->actingAs($this->pustakawan)->get('/books')->assertDontSee('Ajukan Pinjam');

        $this->actingAs($this->account)->get('/my-loans/create')->assertRedirect('/books');
    });

    it('pustakawan menyetujui: stok berkurang, status & jatuh tempo terisi, event BookBorrowed tertembak', function () {
        ($this->submit)([['book_id' => $this->book->id, 'quantity' => 2]]);
        $loan = Loan::sole();
        Event::fake([BookBorrowed::class]);

        $this->actingAs($this->pustakawan)->post("/loans/{$loan->id}/approve")
            ->assertSessionHasNoErrors()->assertRedirect("/loans/{$loan->id}");

        $loan->refresh();
        expect($loan->status)->toBe('borrowed')
            ->and($loan->due_at->isSameDay(now()->addDays(14)))->toBeTrue()
            ->and($loan->borrowed_at)->not->toBeNull()
            ->and($loan->decided_by)->toBe($this->pustakawan->id)
            ->and($this->book->fresh()->stock)->toBe(0)
            ->and($this->member->fresh()->active_loans_count)->toBe(2)
            ->and($this->member->fresh()->status)->toBe('borrowing');

        Event::assertDispatched(BookBorrowed::class);
    });

    it('setelah disetujui, pengembalian & denda sama seperti peminjaman biasa', function () {
        ($this->submit)();
        $loan = Loan::sole();
        $this->actingAs($this->pustakawan)->post("/loans/{$loan->id}/approve");
        $loan->refresh()->update(['due_at' => now()->subDays(2)]);

        $this->actingAs($this->pustakawan)->post("/loans/{$loan->id}/return");

        expect($loan->fresh()->status)->toBe('returned')
            ->and((float) $loan->fresh()->total_fine)->toBe(2000.0)
            ->and($this->book->fresh()->stock)->toBe(2)
            ->and($this->member->fresh()->active_loans_count)->toBe(0);
    });

    it('persetujuan gagal (stok habis saat itu) di-rollback dan pengajuan tetap pending', function () {
        ($this->submit)([['book_id' => $this->book->id, 'quantity' => 2]]);
        $loan = Loan::sole();
        $this->book->update(['stock' => 1]);

        $this->actingAs($this->pustakawan)->from('/loans')->post("/loans/{$loan->id}/approve")
            ->assertSessionHasErrors('stock');

        expect($loan->fresh()->status)->toBe('pending')
            ->and($loan->fresh()->due_at)->toBeNull()
            ->and($this->book->fresh()->stock)->toBe(1)
            ->and($this->member->fresh()->active_loans_count)->toBe(0);
    });

    it('pustakawan menolak dengan alasan: tidak ada perubahan stok, user melihat alasannya', function () {
        ($this->submit)();
        $loan = Loan::sole();

        $this->actingAs($this->pustakawan)->post("/loans/{$loan->id}/reject", ['rejection_reason' => 'Buku sedang dipesan dosen'])
            ->assertSessionHasNoErrors();

        expect($loan->fresh()->status)->toBe('rejected')
            ->and($loan->fresh()->decided_by)->toBe($this->pustakawan->id)
            ->and($this->book->fresh()->stock)->toBe(2)
            ->and($this->member->fresh()->active_loans_count)->toBe(0);

        $this->actingAs($this->account)->get('/my-loans')->assertSee('Ditolak')->assertSee('Buku sedang dipesan dosen');
    });

    it('pengajuan yang ditolak tidak lagi dihitung ke batas pinjaman', function () {
        ($this->submit)([['book_id' => $this->book->id, 'quantity' => 2]]);
        $this->actingAs($this->pustakawan)->post('/loans/'.Loan::sole()->id.'/reject');

        ($this->submit)([['book_id' => $this->book->id, 'quantity' => 2]])->assertSessionHasNoErrors();
    });

    it('pengajuan hanya bisa diproses sekali', function () {
        ($this->submit)();
        $loan = Loan::sole();
        $this->actingAs($this->pustakawan)->post("/loans/{$loan->id}/approve");

        $this->actingAs($this->pustakawan)->post("/loans/{$loan->id}/approve")->assertSessionHasErrors('member');
        $this->actingAs($this->pustakawan)->post("/loans/{$loan->id}/reject")->assertSessionHasErrors('member');

        expect($this->book->fresh()->stock)->toBe(1)->and($loan->fresh()->status)->toBe('borrowed');
    });

    it('user tidak bisa menyetujui, menolak, atau mengembalikan; pengajuan pending tidak bisa "dikembalikan"', function () {
        ($this->submit)();
        $loan = Loan::sole();

        foreach (['approve', 'reject', 'return'] as $action) {
            $this->actingAs($this->account)->post("/loans/{$loan->id}/{$action}")->assertForbidden();
        }

        $this->actingAs($this->pustakawan)->post("/loans/{$loan->id}/return")->assertSessionHasErrors('member');

        expect($loan->fresh()->status)->toBe('pending')->and($this->book->fresh()->stock)->toBe(2);
    });

    it('daftar & detail peminjaman staf aman menampilkan pengajuan (tanggal kosong)', function () {
        ($this->submit)();
        $loan = Loan::sole();

        $this->actingAs($this->pustakawan)->get('/loans')->assertOk()->assertSee('Menunggu');
        $this->actingAs($this->pustakawan)->get('/loans?status=pending')->assertOk()->assertSee($loan->loan_number);
        $this->actingAs($this->pustakawan)->get("/loans/{$loan->id}")->assertOk()->assertSee('Menunggu persetujuan');
        $this->actingAs($this->pustakawan)->get('/dashboard')->assertOk();
    });
});
