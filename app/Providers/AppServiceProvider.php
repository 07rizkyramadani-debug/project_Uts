<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Catalog\Services\BookService;
use Modules\Circulation\Services\LoanQueryService;
use Modules\Membership\Services\MemberService;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Contracts\LoanQueryInterface;
use Modules\Shared\Contracts\MemberServiceInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Contract antar bounded context (Sistem Perpustakaan Digital):
        // Circulation hanya mengenal interface, implementasinya milik Catalog / Membership.
        $this->app->bind(BookServiceInterface::class, BookService::class);
        $this->app->bind(MemberServiceInterface::class, MemberService::class);
        $this->app->bind(LoanQueryInterface::class, LoanQueryService::class);
    }

    public function boot(): void
    {
        //
    }
}
