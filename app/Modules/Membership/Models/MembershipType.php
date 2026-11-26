<?php

namespace Modules\Membership\Models;

use Database\Factories\MembershipTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Jenis keanggotaan (Umum, Mahasiswa, Dosen) beserta batas pinjamnya. */
class MembershipType extends Model
{
    use HasFactory;

    public const DEFAULT_SLUG = 'umum';

    protected $fillable = ['name', 'slug', 'max_loans', 'loan_days'];

    protected $casts = [
        'max_loans' => 'integer',
        'loan_days' => 'integer',
    ];

    /** One-to-Many: satu jenis keanggotaan dimiliki banyak anggota. */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** Jenis bawaan untuk anggota baru; dibuat bila belum ada. */
    public static function default(): self
    {
        return static::firstOrCreate(
            ['slug' => self::DEFAULT_SLUG],
            ['name' => 'Umum', 'max_loans' => Member::MAX_LOANS, 'loan_days' => Member::LOAN_DAYS],
        );
    }

    protected static function newFactory(): MembershipTypeFactory
    {
        return MembershipTypeFactory::new();
    }
}
