<?php

namespace Modules\Membership\Models;

use App\Models\User;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_BORROWING = 'borrowing';

    public const STATUS_SUSPENDED = 'suspended';

    /** Batas pinjam bawaan (jenis "Umum"); jenis lain memakai nilainya sendiri di tabel membership_types. */
    public const MAX_LOANS = 5;

    public const LOAN_DAYS = 14;

    protected $fillable = [
        'user_id', 'membership_type_id', 'member_number', 'name', 'email',
        'phone', 'status', 'active_loans_count', 'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'active_loans_count' => 'integer',
    ];

    /** Nomor anggota unik, dipakai saat admin menambah anggota maupun saat akun baru mendaftar. */
    public static function generateNumber(): string
    {
        return 'AGT-'.now()->format('ym').'-'.strtoupper(substr(uniqid(), -5));
    }

    /** Akun user pemilik data anggota ini (otomatis dibuat saat registrasi) — dipakai untuk halaman "Pinjaman Saya". */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Jenis keanggotaan (Many-to-One): menentukan batas pinjam dan lama pinjam. */
    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function maxLoans(): int
    {
        return $this->membershipType?->max_loans ?? self::MAX_LOANS;
    }

    public function loanDays(): int
    {
        return $this->membershipType?->loan_days ?? self::LOAN_DAYS;
    }

    protected static function booted(): void
    {
        // Anggota baru tanpa jenis yang dipilih otomatis menjadi "Umum"
        static::creating(function (Member $member) {
            $member->membership_type_id ??= MembershipType::default()->id;
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_SUSPENDED);
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('member_number', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%");
        });
    }

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }
}
