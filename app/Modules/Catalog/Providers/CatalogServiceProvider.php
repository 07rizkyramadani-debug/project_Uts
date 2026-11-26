<?php

namespace Modules\Catalog\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Publisher;

class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Memuat migrasi dan route milik bounded context ini
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Jumlah data master pada tab (Penulis / Kategori / Penerbit)
        View::composer('library._master_tabs', fn ($view) => $view->with('masterCounts', [
            'authors' => Author::count(),
            'categories' => Category::count(),
            'publishers' => Publisher::count(),
        ]));
    }
}
