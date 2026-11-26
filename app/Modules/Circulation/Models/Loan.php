<?php

namespace Modules\Circulation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Circulation\ValueObjects\Denda;

class Loan extends Model
{
    use SoftDeletes;

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'member_id', 'member_number', 'member_name', 'loan_number',
        'borrowed_at', 'due_at', 'returned_at', 'status', 'total_fine', 'notes',
        'decided_by', 'decided_at', 'rejection_reason',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
        'decided_at' => 'datetime',
        'total_fine' => 'decimal:2',
    ];

    /** HasMany: Peminjaman -> DetailPeminjaman */
    public function details(): HasMany
    {
        return $this->hasMany(LoanDetail::class);
    }

    public function scopeBorrowed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BORROWED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->borrowed()->where('due_at', '<', now());
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isBorrowed(): bool
    {
        return $this->status === self::STATUS_BORROWED;
    }

    public function isOverdue(): bool
    {
        return $this->isBorrowed() && $this->due_at !== null && $this->due_at->isPast();
    }

    /**
     * Denda yang berlaku saat ini: nilai final bila sudah dikembalikan; bila masih dipinjam,
     * denda berjalan (hari terlambat x tarif x jumlah eksemplar). Pengajuan tidak berdenda.
     * Relasi `details` sebaiknya sudah di-eager load.
     */
    public function accruedFine(): float
    {
        if ($this->isReturned()) {
            return (float) $this->total_fine;
        }

        if (! $this->isBorrowed() || $this->due_at === null) {
            return 0.0;
        }

        return Denda::hitung($this->due_at, now())->getAmount() * (int) $this->details->sum('quantity');
    }
}
