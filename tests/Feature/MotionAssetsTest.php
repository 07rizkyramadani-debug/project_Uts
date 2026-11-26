<?php

use App\Models\User;

it('halaman setelah login untuk semua peran memuat aset animasi', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertSee('css/motion.css', false)
        ->assertSee('js/motion.js', false);
})->with(['user', 'pustakawan', 'admin']);

it('halaman login dan register memuat aset animasi', function () {
    foreach (['/login', '/register'] as $url) {
        $this->get($url)->assertOk()->assertSee('css/motion.css', false)->assertSee('js/motion.js', false);
    }
});

it('berkas animasi ada dan menghormati pengaturan kurangi gerakan', function () {
    $css = file_get_contents(public_path('css/motion.css'));
    $js = file_get_contents(public_path('js/motion.js'));

    expect($css)->toContain('prefers-reduced-motion: reduce')
        ->and($js)->toContain('prefers-reduced-motion: reduce');
});

it('halaman awal publik memakai animasinya sendiri dan tidak memuat motion.js', function () {
    $this->get('/')->assertOk()->assertDontSee('js/motion.js', false);
});
