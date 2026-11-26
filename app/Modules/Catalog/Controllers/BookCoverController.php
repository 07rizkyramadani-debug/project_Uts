<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyajikan gambar sampul buku langsung lewat aplikasi, sehingga sampul tetap tampil
 * tanpa bergantung pada "php artisan storage:link" maupun nilai APP_URL di .env.
 */
class BookCoverController extends Controller
{
    private const CACHE = ['Cache-Control' => 'public, max-age=86400'];

    public function show(string $file): Response
    {
        $disk = Storage::disk('public');
        $path = 'covers/'.$file;

        if ($disk->exists($path)) {
            return $disk->response($path, null, self::CACHE);
        }

        // Cadangan: sampul bawaan seeder (database/seeders/covers) bila file belum tersalin ke storage
        $seeded = database_path('seeders/covers/'.$file);

        abort_unless(is_file($seeded), 404);

        return response()->file($seeded, self::CACHE);
    }
}
