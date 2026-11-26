<?php

namespace Modules\Membership\Services;

use App\Models\User;
use Modules\Membership\Models\Member;
use Modules\Shared\Contracts\MemberServiceInterface;

class MemberService implements MemberServiceInterface
{
    public function getMemberForLoan(int $memberId): ?array
    {
        $member = Member::find($memberId);

        return $member ? $this->toLoanData($member) : null;
    }

    public function getMemberForUser(int $userId): ?array
    {
        // Akun lama yang belum punya data anggota ikut diaktifkan otomatis
        return $this->ensureMemberForUser($userId);
    }

    public function ensureMemberForUser(int $userId): ?array
    {
        $member = Member::where('user_id', $userId)->first();

        if (! $member) {
            $user = User::find($userId);

            // Hanya akun ber-role "user"; anggota yang sengaja dihapus admin tidak dibuat ulang
            if (! $user || ! $user->isUser() || Member::onlyTrashed()->where('user_id', $userId)->exists()) {
                return null;
            }

            $member = $this->activateMemberFor($user);
        }

        return $member ? $this->toLoanData($member) : null;
    }

    /** Pakai data anggota ber-email sama yang belum punya akun; jika tidak ada, buat anggota baru yang aktif. */
    private function activateMemberFor(User $user): ?Member
    {
        $existing = Member::withTrashed()->where('email', $user->email)->first();

        if ($existing) {
            if ($existing->trashed() || $existing->user_id) {
                return null;
            }

            $existing->update(['user_id' => $user->id]);

            return $existing;
        }

        return Member::create([
            'user_id' => $user->id,
            'member_number' => Member::generateNumber(),
            'name' => $user->name,
            'email' => $user->email,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
    }

    /** @return array{id:int, member_number:string, name:string, status:string, active_loans:int, max_loans:int, loan_days:int} */
    private function toLoanData(Member $member): array
    {
        return [
            'id' => $member->id,
            'member_number' => $member->member_number,
            'name' => $member->name,
            'status' => $member->status,
            'active_loans' => $member->active_loans_count,
            'max_loans' => $member->maxLoans(),
            'loan_days' => $member->loanDays(),
        ];
    }

    public function getBorrowBlockReason(int $memberId, int $quantity): ?string
    {
        $member = Member::find($memberId);

        if (! $member) {
            return 'Anggota tidak ditemukan.';
        }

        if ($member->status === Member::STATUS_SUSPENDED) {
            return "Anggota {$member->name} sedang ditangguhkan (suspended).";
        }

        $max = $member->maxLoans();
        if ($member->active_loans_count + $quantity > $max) {
            return "Batas pinjaman anggota {$member->name} terlampaui "
                ."(sedang meminjam {$member->active_loans_count}, maksimal {$max}).";
        }

        return null;
    }

    public function markBorrowing(int $memberId, int $quantity): bool
    {
        $member = Member::lockForUpdate()->find($memberId);

        if (! $member) {
            return false;
        }

        $member->increment('active_loans_count', $quantity);
        $member->update(['status' => Member::STATUS_BORROWING]);

        return true;
    }

    public function markReturned(int $memberId, int $quantity): bool
    {
        $member = Member::withTrashed()->lockForUpdate()->find($memberId);

        if (! $member) {
            return false;
        }

        $remaining = max(0, $member->active_loans_count - $quantity);
        $member->active_loans_count = $remaining;

        if ($member->status === Member::STATUS_BORROWING && $remaining === 0) {
            $member->status = Member::STATUS_ACTIVE;
        }

        $member->save();

        return true;
    }

    public function listActiveMembers(): array
    {
        return Member::active()
            ->orderBy('name')
            ->get(['id', 'member_number', 'name'])
            ->map(fn (Member $m) => [
                'id' => $m->id,
                'member_number' => $m->member_number,
                'name' => $m->name,
            ])
            ->all();
    }
}
