<?php

use Modules\Catalog\Services\BookService;
use Modules\Membership\Services\MemberService;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Contracts\MemberServiceInterface;

/*
 | Bukti otomatis aturan Modular Monolith: tidak ada akses langsung antar modul.
 | Komunikasi hanya lewat Contract (Modules\Shared\Contracts) atau Domain Event.
 */

arch('Catalog tidak mengakses modul Membership maupun Circulation')
    ->expect('Modules\Catalog')
    ->not->toUse(['Modules\Membership', 'Modules\Circulation']);

arch('Membership tidak mengakses modul Catalog maupun Circulation')
    ->expect('Modules\Membership')
    ->not->toUse(['Modules\Catalog', 'Modules\Circulation']);

arch('Circulation tidak mengakses model/tabel modul Catalog dan Membership secara langsung')
    ->expect('Modules\Circulation')
    ->not->toUse(['Modules\Catalog', 'Modules\Membership']);

arch('Contract antar modul berupa interface')
    ->expect('Modules\Shared\Contracts')
    ->toBeInterfaces();

arch('Custom Exception turunan Exception')
    ->expect('Modules\Shared\Exceptions')
    ->toExtend(Exception::class);

it('Service milik modul mengimplementasikan Contract-nya', function () {
    expect(app(BookServiceInterface::class))->toBeInstanceOf(BookService::class)
        ->and(app(MemberServiceInterface::class))->toBeInstanceOf(MemberService::class);
});

it('setiap modul memiliki Service Provider sendiri yang terdaftar', function () {
    $providers = array_keys(app()->getLoadedProviders());

    foreach (['Auth', 'Catalog', 'Membership', 'Circulation'] as $module) {
        expect($providers)->toContain("Modules\\{$module}\\Providers\\{$module}ServiceProvider");
    }
});

it('Service Provider tiap modul memuat migrasi dan route sendiri', function () {
    foreach (['Auth', 'Catalog', 'Membership', 'Circulation'] as $module) {
        $source = file_get_contents(app_path("Modules/{$module}/Providers/{$module}ServiceProvider.php"));

        expect($source)->toContain('loadMigrationsFrom')->toContain('loadRoutesFrom');
    }
});
