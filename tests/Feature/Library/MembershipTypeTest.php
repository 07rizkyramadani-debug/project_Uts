<?php

use App\Models\User;
use Modules\Catalog\Models\Book;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\MembershipType;
use Modules\Membership\Services\MemberService;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->pustakawan = User::factory()->create(['role' => 'pustakawan']);
});

it('migrasi menyediakan jenis bawaan Umum, Mahasiswa, dan Dosen', function () {
    expect(MembershipType::pluck('slug')->all())->toContain('umum', 'mahasiswa', 'dosen');
});

it('anggota baru tanpa jenis otomatis menjadi Umum', function () {
    $member = Member::factory()->create();

    expect($member->membershipType->slug)->toBe('umum')
        ->and($member->maxLoans())->toBe(5)
        ->and($member->loanDays())->toBe(14);
});

it('batas pinjam dan lama pinjam mengikuti jenis keanggotaan', function () {
    $dosen = MembershipType::firstWhere('slug', 'dosen');
    $member = Member::factory()->create(['membership_type_id' => $dosen->id]);

    $data = app(MemberService::class)->getMemberForLoan($member->id);

    expect($data['max_loans'])->toBe(10)->and($data['loan_days'])->toBe(30);
});

it('batas pinjam jenis keanggotaan dipakai saat validasi peminjaman', function () {
    $small = MembershipType::factory()->create(['max_loans' => 2, 'loan_days' => 7]);
    $member = Member::factory()->create(['membership_type_id' => $small->id]);

    expect(app(MemberService::class)->getBorrowBlockReason($member->id, 3))->toContain('Batas pinjaman')
        ->and(app(MemberService::class)->getBorrowBlockReason($member->id, 2))->toBeNull();
});

it('admin menambah, mengubah, dan menghapus jenis keanggotaan', function () {
    $this->actingAs($this->admin)->post('/membership-types', ['name' => 'Alumni', 'max_loans' => 4, 'loan_days' => 10])
        ->assertSessionHasNoErrors()->assertRedirect('/membership-types');

    $type = MembershipType::firstWhere('slug', 'alumni');
    expect($type->max_loans)->toBe(4);

    $this->actingAs($this->admin)->put("/membership-types/{$type->id}", ['name' => 'Alumni', 'max_loans' => 6, 'loan_days' => 10])
        ->assertSessionHasNoErrors();
    expect($type->fresh()->max_loans)->toBe(6);

    $this->actingAs($this->admin)->delete("/membership-types/{$type->id}")->assertRedirect('/membership-types');
    expect(MembershipType::find($type->id))->toBeNull();
});

it('validasi jenis keanggotaan menampilkan pesan error yang jelas', function () {
    $this->actingAs($this->admin)->post('/membership-types', ['name' => '', 'max_loans' => 0, 'loan_days' => 99])
        ->assertSessionHasErrors([
            'name' => 'Nama jenis keanggotaan wajib diisi.',
            'max_loans' => 'Batas pinjam harus antara 1 sampai 20 buku.',
            'loan_days' => 'Lama pinjam harus antara 1 sampai 30 hari.',
        ]);
});

it('jenis yang masih dipakai anggota atau jenis bawaan tidak bisa dihapus', function () {
    $dosen = MembershipType::firstWhere('slug', 'dosen');
    Member::factory()->create(['membership_type_id' => $dosen->id]);

    $this->actingAs($this->admin)->from('/membership-types')->delete("/membership-types/{$dosen->id}")->assertSessionHasErrors('delete');
    $this->actingAs($this->admin)->from('/membership-types')->delete('/membership-types/'.MembershipType::firstWhere('slug', 'umum')->id)->assertSessionHasErrors('delete');

    expect(MembershipType::find($dosen->id))->not->toBeNull();
});

it('pustakawan dan user biasa tidak boleh mengelola jenis keanggotaan', function () {
    $this->actingAs($this->pustakawan)->get('/membership-types')->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => 'user']))->get('/membership-types')->assertForbidden();
});

it('form anggota memilih jenis keanggotaan dan menolak jenis yang tidak ada', function () {
    $mhs = MembershipType::firstWhere('slug', 'mahasiswa');

    $this->actingAs($this->admin)->post('/members', ['name' => 'Ani', 'email' => 'ani@gmail.com', 'membership_type_id' => $mhs->id])
        ->assertSessionHasNoErrors();
    expect(Member::firstWhere('email', 'ani@gmail.com')->membership_type_id)->toBe($mhs->id);

    $this->actingAs($this->admin)->post('/members', ['name' => 'Ina', 'email' => 'ina@gmail.com', 'membership_type_id' => 9999])
        ->assertSessionHasErrors(['membership_type_id' => 'Jenis keanggotaan tidak ditemukan.']);
});

it('laporan stok memakai Form Request dengan pesan error informatif', function () {
    $book = Book::factory()->outOfStock()->create();

    $this->actingAs($this->pustakawan)->post("/books/{$book->id}/report-stock", ['note' => str_repeat('x', 300)])
        ->assertSessionHasErrors(['note' => 'Catatan laporan maksimal 255 karakter.']);
});
