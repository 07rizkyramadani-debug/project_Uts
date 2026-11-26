<?php

namespace Modules\Circulation\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Requests\StoreLoanRequest;
use Modules\Circulation\Services\LoanService;

/**
 * Endpoint REST sirkulasi. Error bisnis (stok habis, anggota diblokir) dilempar sebagai
 * Custom Exception dan otomatis dirender sebagai JSON 422 oleh method render() exception.
 */
class LoanApiController extends Controller
{
    public function __construct(private LoanService $loanService) {}

    public function store(StoreLoanRequest $request): JsonResponse
    {
        $data = $request->validated();

        $loan = $this->loanService->borrow(
            (int) $data['member_id'],
            $data['items'],
            isset($data['loan_days']) ? (int) $data['loan_days'] : null,
            $data['notes'] ?? null
        );

        return response()->json(['message' => 'Peminjaman berhasil dicatat.', 'data' => $loan], 201);
    }

    public function returnLoan(Loan $loan): JsonResponse
    {
        $loan = $this->loanService->returnLoan($loan);

        return response()->json(['message' => 'Buku dikembalikan.', 'data' => $loan]);
    }
}
