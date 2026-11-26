<?php

namespace Modules\Circulation\Services;

use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanDetail;
use Modules\Shared\Contracts\LoanQueryInterface;

class LoanQueryService implements LoanQueryInterface
{
    private const ACTIVE_STATUSES = [Loan::STATUS_BORROWED, Loan::STATUS_PENDING];

    public function hasActiveLoansForBook(int $bookId): bool
    {
        return LoanDetail::where('book_id', $bookId)
            ->whereHas('loan', fn ($q) => $q->whereIn('status', self::ACTIVE_STATUSES))
            ->exists();
    }

    public function hasActiveLoansForMember(int $memberId): bool
    {
        return Loan::where('member_id', $memberId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->exists();
    }
}
