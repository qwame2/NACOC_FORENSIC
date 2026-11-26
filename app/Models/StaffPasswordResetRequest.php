<?php

namespace App\Models;

use App\Enums\ForensicUnit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StaffPasswordResetRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'staff_account_id',
        'user_id',
        'fullname',
        'username',
        'service_id',
        'email',
        'phone_number',
        'unit',
        'unit_slug',
        'status',
        'ip_address',
        'user_agent',
        'reviewed_by_id',
        'reviewed_by_name',
        'reviewed_at',
        'review_note',
        'otp_sent_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'otp_sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function staffAccount()
    {
        return $this->belongsTo(StaffAccount::class, 'staff_account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * Normalize an officer unit string into its canonical slug ('css', 'em', 'lab', 'dpf', 'directorate').
     */
    public static function normalizeUnitSlug(?string $unit): string
    {
        $opSlug = ForensicUnit::resolveOperationalSlug($unit);
        if ($opSlug !== null) {
            return $opSlug;
        }

        $resolved = ForensicUnit::resolve($unit);
        return $resolved ? $resolved->slug() : 'css';
    }

    /**
     * Scope password reset requests to a Unit Head's operational unit (or all units for Forensic Directorate).
     */
    public function scopeForUnitContext(Builder $query, ?string $unitOrSlug = null, ?Authenticatable $viewer = null): Builder
    {
        $source = trim((string) ($unitOrSlug ?: ($viewer?->unit ?? '')));
        if ($source === '' || strcasecmp($source, 'all') === 0 || strcasecmp($source, 'all units') === 0) {
            return $query;
        }

        $opSlug = ForensicUnit::resolveOperationalSlug($source);
        if ($opSlug !== null) {
            $terms = ForensicUnit::operationalSearchTerms($opSlug);
            return $query->where(function (Builder $q) use ($opSlug, $terms) {
                $q->where('unit_slug', $opSlug);
                foreach ($terms as $term) {
                    $q->orWhereRaw('LOWER(COALESCE(unit, "")) LIKE ?', ['%' . $term . '%']);
                }
            });
        }

        $resolved = ForensicUnit::resolve($source);
        if ($resolved === ForensicUnit::FORENSIC_DIRECTORATE) {
            // Forensic Directorate Head sees requests across units (or directorate staff)
            return $query;
        }

        return $query;
    }

    /**
     * Count pending staff password reset requests for sidebar & topbar badges.
     */
    public static function countPendingForContext(?string $unitName = null, ?Authenticatable $viewer = null): int
    {
        try {
            if (!Schema::hasTable('staff_password_reset_requests')) {
                return 0;
            }

            return static::query()
                ->forUnitContext($unitName, $viewer)
                ->where('status', self::STATUS_PENDING)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

