<?php

namespace Modules\Shared\Exceptions;

use Exception;

/**
 * Dilempar saat data (buku, anggota, penulis, kategori, penerbit) tidak boleh dihapus
 * karena masih dipakai data lain.
 */
class ResourceInUseException extends Exception
{
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => 'resource_in_use',
            ], 422);
        }

        return back()->withErrors(['delete' => $this->getMessage()]);
    }
}
