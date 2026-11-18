<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAudit extends Model
{
    use HasFactory;

    protected $table = 'staff_audit';

    protected $fillable = [
        'user_id',
        'staff_account_id',
        'staff_name',
        'service_id',
        'staff_email',
        'unit',
        'role',
        'action',
        'description',
        'route',
        'url',
        'method',
        'status_code',
        'ip_address',
        'user_agent',
        'payload',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staffAccount(): BelongsTo
    {
        return $this->belongsTo(StaffAccount::class, 'staff_account_id');
    }

    /**
     * Convenient static logger method for custom activity events.
     */
    public static function logActivity(array $data): self
    {
        return self::create([
            'user_id'          => $data['user_id'] ?? auth()->id(),
            'staff_account_id' => $data['staff_account_id'] ?? (auth()->user() instanceof StaffAccount ? (string) auth()->user()->id : null),
            'staff_name'       => $data['staff_name'] ?? (auth()->user()?->fullname ?? 'Unknown Staff'),
            'service_id'       => $data['service_id'] ?? (auth()->user()?->service_id ?? null),
            'staff_email'      => $data['staff_email'] ?? (auth()->user()?->email ?? null),
            'unit'             => $data['unit'] ?? (auth()->user()?->unit ?? 'Operational Unit'),
            'role'             => $data['role'] ?? (auth()->user()?->role ?? 'Staff'),
            'action'           => strtoupper($data['action'] ?? 'ACTION'),
            'description'      => $data['description'] ?? 'Staff performed an action',
            'route'            => $data['route'] ?? request()->path(),
            'url'              => $data['url'] ?? request()->fullUrl(),
            'method'           => strtoupper($data['method'] ?? request()->method()),
            'status_code'      => $data['status_code'] ?? 200,
            'ip_address'       => $data['ip_address'] ?? request()->ip(),
            'user_agent'       => $data['user_agent'] ?? request()->userAgent(),
            'payload'          => $data['payload'] ?? null,
        ]);
    }

    /**
     * Scope query to a specific forensic unit.
     */
    public function scopeForUnit($query, ?string $unit)
    {
        if (empty($unit) || strtoupper($unit) === 'ALL' || strtolower($unit) === 'all') {
            return $query;
        }

        $clean = trim($unit);
        return $query->where(function ($q) use ($clean) {
            $q->where('unit', 'LIKE', '%' . $clean . '%');
            if (str_contains(strtolower($clean), 'lab')) {
                $q->orWhere('unit', 'LIKE', '%Lab%');
            } elseif (str_contains(strtolower($clean), 'evidence')) {
                $q->orWhere('unit', 'LIKE', '%Evidence%');
            } elseif (str_contains(strtolower($clean), 'digital') || str_contains(strtolower($clean), 'physical')) {
                $q->orWhere('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%');
            } elseif (str_contains(strtolower($clean), 'crime')) {
                $q->orWhere('unit', 'LIKE', '%Crime%');
            }
        });
    }

    /**
     * Scope query by search query term across name, service ID, description, route, or IP.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);
        return $query->where(function ($q) use ($term) {
            $q->where('staff_name', 'LIKE', "%{$term}%")
              ->orWhere('service_id', 'LIKE', "%{$term}%")
              ->orWhere('staff_email', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%")
              ->orWhere('route', 'LIKE', "%{$term}%")
              ->orWhere('action', 'LIKE', "%{$term}%")
              ->orWhere('ip_address', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Scope query by action type.
     */
    public function scopeAction($query, ?string $action)
    {
        if (empty($action) || strtoupper($action) === 'ALL') {
            return $query;
        }

        return $query->where('action', strtoupper(trim($action)));
    }

    /**
     * Scope query by relative date range.
     */
    public function scopeDateRange($query, ?string $range)
    {
        return match ($range) {
            'today'    => $query->whereDate('created_at', today()),
            '7_days'   => $query->where('created_at', '>=', now()->subDays(7)),
            '30_days'  => $query->where('created_at', '>=', now()->subDays(30)),
            '90_days'  => $query->where('created_at', '>=', now()->subDays(90)),
            default    => $query,
        };
    }
}

