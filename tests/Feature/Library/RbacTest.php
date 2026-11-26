<?php

use App\Models\User;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Models\Loan;
use Modules\Membership\Models\Member;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->pustakawan = User::factory()->create(['role' => 'pustakawan']);
    $this->user = User::factory()->create(['role' => 'user']);
});

it('tamu diarahkan ke halaman login', function () {
    $this->get('/books')->assertRedirect('/login');
    $this->get('/loans')->assertRedirect('/login');
});

it('user biasa boleh melihat katalog tetapi tidak boleh mengelola sirkulasi dan anggota', function () {
    $this->actingAs($this->user)->get('/books')->assertOk();
    $this->actingAs($this->user)->get('/loans')->assertForbidden();
    $this->actingAs($this->user)->get('/loans/create')->assertForbidden();
    $this->actingAs($this->user)->get('/members')->assertForbidden();
    $this->actingAs($this->user)->get('/books/create')->assertForbidden();
});

it('pustakawan boleh mengelola peminjaman dan mendaftarkan anggota, tetapi bukan CRUD buku, ubah/hapus anggota, atau hapus peminjaman', function () {
    $this->actingAs($this->pustakawan)->get('/loans')->assertOk();
    $this->actingAs($this->pustakawan)->get('/loans/create')->assertOk();
    $this->actingAs($this->pustakawan)->get('/members')->assertOk();
    $this->actingAs($this->pustakawan)->get('/books/create')->assertForbidden();
    $this->actingAs($this->pustakawan)->get('/members/create')->assertOk();
    $this->actingAs($this->pustakawan)->get('/members/trashed')->assertForbidden();
    $this->actingAs($this->pustakawan)->get('/loans/trashed')->assertForbidden();
});

it('admin boleh mengakses seluruh fitur', function () {
    $this->actingAs($this->admin)->get('/books/create')->assertOk();
    $this->actingAs($this->admin)->get('/members/create')->assertOk();
    $this->actingAs($this->admin)->get('/loans/trashed')->assertOk();
    $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk();
});

it('pustakawan tidak boleh membuka dashboard admin', function () {
    $this->actingAs($this->pustakawan)->get('/admin/dashboard')->assertForbidden();
});

describe('API sirkulasi (Sanctum + RBAC)', function () {
    beforeEach(function () {
        $this->member = Member::factory()->create();
        $this->book = Book::factory()->create(['stock' => 2, 'total_stock' => 2]);
    });

    it('user biasa dengan token valid tetap ditolak 403 saat meminjam', function () {
        $token = $this->user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/loans', [
            'member_id' => $this->member->id,
            'items' => [['book_id' => $this->book->id, 'quantity' => 1]],
        ])->assertForbidden();

        expect(Loan::count())->toBe(0);
    });

    it('pustakawan berhasil membuat peminjaman lewat API', function () {
        $token = $this->pustakawan->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/loans', [
            'member_id' => $this->member->id,
            'items' => [['book_id' => $this->book->id, 'quantity' => 1]],
        ])->assertCreated()->assertJsonPath('data.status', 'borrowed');

        expect($this->book->fresh()->stock)->toBe(1);
    });

    it('CUSTOM EXCEPTION: stok kurang dirender sebagai JSON 422 informatif dan di-rollback', function () {
        $token = $this->pustakawan->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/loans', [
            'member_id' => $this->member->id,
            'items' => [['book_id' => $this->book->id, 'quantity' => 3]],
        ])->assertUnprocessable()
            ->assertJsonPath('error', 'book_not_available')
            ->assertJsonFragment(['message' => "Buku '{$this->book->title}' tidak tersedia dalam jumlah yang diminta. Stok: 2, diminta: 3."]);

        expect(Loan::count())->toBe(0)->and($this->book->fresh()->stock)->toBe(2);
    });

    it('validasi Form Request: item kosong ditolak 422', function () {
        $token = $this->pustakawan->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/loans', ['member_id' => $this->member->id, 'items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    });
});

it('halaman web menampilkan pesan error custom exception di view', function () {
    $member = Member::factory()->create();
    $book = Book::factory()->create(['stock' => 1, 'total_stock' => 1]);

    $this->actingAs($this->pustakawan)
        ->from('/loans/create')
        ->post('/loans', ['member_id' => $member->id, 'items' => [['book_id' => $book->id, 'quantity' => 2]]])
        ->assertRedirect('/loans/create')
        ->assertSessionHasErrors('stock');

    $this->actingAs($this->pustakawan)->get('/loans/create')->assertOk();
});

it('login lewat form (sesi nyata) lalu membuka halaman terproteksi berhasil', function () {
    $admin = User::factory()->create(['role' => 'admin', 'password' => 'password123']);

    $this->post('/login', ['email' => $admin->email, 'password' => 'password123'])->assertRedirect('/dashboard');

    $this->get('/admin/dashboard')->assertOk();
    $this->get('/user/dashboard')->assertOk();
    $this->get('/books')->assertOk();
});

it('form peminjaman web: baris buku tambahan yang kosong diabaikan dan peminjaman tetap berhasil', function () {
    $member = Member::factory()->create();
    $book = Book::factory()->create(['stock' => 3, 'total_stock' => 3]);

    $this->actingAs($this->pustakawan)->post('/loans', [
        'member_id' => $member->id,
        'items' => [
            ['book_id' => $book->id, 'quantity' => 1],
            ['book_id' => '', 'quantity' => 1],
            ['book_id' => '', 'quantity' => 1],
        ],
    ])->assertSessionHasNoErrors();

    expect(Loan::count())->toBe(1)->and($book->fresh()->stock)->toBe(2);
});
