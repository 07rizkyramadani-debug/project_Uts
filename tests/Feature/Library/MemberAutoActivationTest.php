<?php

use App\Models\User;
use Modules\Membership\Models\Member;

it('akun yang mendaftar langsung menjadi anggota aktif tanpa penautan manual', function () {
    $this->post('/register', [
        'name' => 'Siti Aminah',
        'email' => 'siti@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::firstWhere('email', 'siti@gmail.com');
    $member = Member::firstWhere('user_id', $user->id);

    expect($member)->not->toBeNull()
        ->and($member->status)->toBe('active')
        ->and($member->name)->toBe('Siti Aminah')
        ->and($member->email)->toBe('siti@gmail.com')
        ->and($member->member_number)->toStartWith('AGT-');
});

it('akun yang mendaftar bisa langsung membuka Pinjaman Saya', function () {
    $this->post('/register', [
        'name' => 'Budi', 'email' => 'budi@gmail.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ]);

    $this->get('/my-loans')->assertOk()->assertDontSee('tidak aktif');
});

it('memakai data anggota ber-email sama yang dibuat admin lebih dulu', function () {
    $existing = Member::factory()->create(['email' => 'rina@gmail.com']);

    $this->post('/register', [
        'name' => 'Rina', 'email' => 'rina@gmail.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ]);

    expect(Member::where('email', 'rina@gmail.com')->count())->toBe(1)
        ->and($existing->fresh()->user_id)->toBe(User::firstWhere('email', 'rina@gmail.com')->id);
});

it('admin dan pustakawan tidak dibuatkan data anggota', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/dashboard')->assertOk();

    expect(Member::where('user_id', $admin->id)->exists())->toBeFalse();
});

it('form anggota tidak lagi menampilkan pilihan akun user', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/members/create')
        ->assertOk()->assertDontSee('Akun user')->assertDontSee('Tidak ditautkan');
});

it('daftar anggota tidak menampilkan peringatan "belum tertaut ke akun user"', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Member::factory()->count(3)->create();

    $this->actingAs($admin)->get('/members')
        ->assertOk()->assertDontSee('Belum tertaut ke akun user');
});

it('email anggota hasil factory memakai gmail.com', function () {
    expect(Member::factory()->create()->email)->toEndWith('@gmail.com');
});
