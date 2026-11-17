<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class StaffAccount extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'staff_accounts';

    protected $fillable = [
        'user_id',
        'fullname',
        'username',
        'service_id',
        'phone_number',
        'email',
        'unit',
        'is_approved',
        'last_seen_at',
        'permissions',
        'avatar',
        'signature',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'last_seen_at' => 'datetime',
        'permissions' => 'array',
        'password' => 'hashed',
    ];

    public function getAuthIdentifier()
    {
        return 'staff:' . $this->getKey();
    }

    public function getIsStaffAttribute(): bool
    {
        return true;
    }

    public function getIsHeadAttribute(): bool
    {
        return false;
    }

    public function effectivePermissions(): array
    {
        $keys = array_keys(User::AVAILABLE_PERMISSIONS);
        $stored = is_array($this->permissions) ? $this->permissions : null;
        $staffUnitSlug = \App\Enums\ForensicUnit::resolveOperationalSlug($this->unit);
        $storedUnitSlug = is_array($stored) && isset($stored['_unit_slug'])
            ? \App\Enums\ForensicUnit::resolveOperationalSlug((string) $stored['_unit_slug'])
            : null;
        $unitMismatch = $storedUnitSlug !== null && $staffUnitSlug !== null && $storedUnitSlug !== $staffUnitSlug;

        $resolved = [];
        foreach ($keys as $key) {
            if ($stored === null || $unitMismatch) {
                $resolved[$key] = (bool) $this->is_approved;
            } else {
                $resolved[$key] = (bool) ($stored[$key] ?? false);
            }
        }

        return $resolved;
    }

    public static function resolveUnitSlugFromContext(?string $unitOrPath): ?string
    {
        if ($unitOrPath === null || trim($unitOrPath) === '') {
            return null;
        }

        $trimmed = trim($unitOrPath, " \t\n\r\0\x0B/");
        if ($trimmed === '') {
            return null;
        }

        $lower = strtolower($trimmed);
        if (str_starts_with($lower, 'crime_scene_support') || str_contains($lower, '/crime_scene_support')) {
            return 'css';
        }
        if (str_starts_with($lower, 'lab_analysis') || str_contains($lower, '/lab_analysis')) {
            return 'lab';
        }
        if (str_starts_with($lower, 'evidence_management') || str_contains($lower, '/evidence_management')) {
            return 'em';
        }
        if (str_starts_with($lower, 'digital_forensics') || str_contains($lower, '/digital_forensics')) {
            return 'dpf';
        }

        return \App\Enums\ForensicUnit::resolveOperationalSlug($trimmed);
    }

    public function hasPermission(string $permission, ?string $unitOrPath = null): bool
    {
        if (!$this->is_approved) {
            return false;
        }

        if ($unitOrPath !== null && trim($unitOrPath) !== '') {
            $contextSlug = self::resolveUnitSlugFromContext($unitOrPath);
            $staffUnitSlug = \App\Enums\ForensicUnit::resolveOperationalSlug($this->unit);
            if ($contextSlug !== null && $staffUnitSlug !== null && $contextSlug !== $staffUnitSlug) {
                return true;
            }
        }

        $map = $this->effectivePermissions();
        return (bool) ($map[$permission] ?? false);
    }

    public function isOnline(): bool
    {
        if (!$this->is_approved) {
            return false;
        }

        if (auth()->check() && auth()->user() instanceof self && (int) auth()->user()->id === (int) $this->id) {
            return true;
        }

        try {
            if (\Illuminate\Support\Facades\Cache::has('staff_online_' . $this->id)) {
                return true;
            }
        } catch (\Throwable $e) {
            // Fall back to database last_seen_at if cache store is unavailable
        }

        if ($this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(5))) {
            return true;
        }

        return false;
    }

    public function markOnline(): void
    {
        try {
            \Illuminate\Support\Facades\Cache::put('staff_online_' . $this->id, true, now()->addMinutes(5));
        } catch (\Throwable $e) {
            // Ignore cache errors so authentication never fails if cache store is down
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('staff_accounts', 'last_seen_at')) {
            if (!$this->last_seen_at || $this->last_seen_at->lt(now()->subSeconds(60))) {
                $this->timestamps = false;
                $this->forceFill(['last_seen_at' => now()])->saveQuietly();
                $this->timestamps = true;
            }
        }
    }

    public function markOffline(): void
    {
        try {
            \Illuminate\Support\Facades\Cache::forget('staff_online_' . $this->id);
        } catch (\Throwable $e) {
            // Ignore cache errors so logout never fails if cache store is down
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('staff_accounts', 'last_seen_at')) {
            $this->timestamps = false;
            $this->forceFill(['last_seen_at' => null])->saveQuietly();
            $this->timestamps = true;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
