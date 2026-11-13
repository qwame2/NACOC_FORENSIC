<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Canonical operational forensic permissions managed by Unit Heads.
     *
     * @var array<string, array{label: string, description: string, category: string}>
     */
    public const AVAILABLE_PERMISSIONS = [
        'create_cases' => [
            'label'       => 'Create New Cases',
            'description' => 'Intake new single/multiple suspect or exhibit-only cases',
            'category'    => 'Cases',
        ],
        'edit_cases' => [
            'label'       => 'Edit Case & Suspects',
            'description' => 'Update case details, suspect biodata, and offence records',
            'category'    => 'Cases',
        ],
        'manage_exhibits' => [
            'label'       => 'Manage Exhibit Vault',
            'description' => 'Log exhibits, update seized items, and assign QR custody tags',
            'category'    => 'Evidence',
        ],
        'record_lab_analysis' => [
            'label'       => 'Lab & Drug Screening',
            'description' => 'Enter laboratory examination findings and screening logs',
            'category'    => 'Lab',
        ],
        'record_digital_forensics' => [
            'label'       => 'Digital Forensics Intake',
            'description' => 'Upload extraction reports, device metadata, and hash verification',
            'category'    => 'Digital',
        ],
        'generate_reports' => [
            'label'       => 'Generate Reports & PDFs',
            'description' => 'Export preliminary, analytical, SITREP, and unit audit reports',
            'category'    => 'Reports',
        ],
        'view_notifications' => [
            'label'       => 'Notifications & Handovers',
            'description' => 'Access unit notification feeds and acknowledge case handovers',
            'category'    => 'Alerts',
        ],
    ];

    /**
     * Core permissions shown on the permissions page for Crime Scene Support and Digital Forensics.
     *
     * @var list<string>
     */
    public const STREAMLINED_PERMISSION_KEYS = [
        'create_cases',
        'edit_cases',
        'generate_reports',
    ];

    /**
     * Return the applicable permission definitions for a given unit or route context.
     * Crime Scene Support and Digital Forensics display only the 4 core case/report permissions.
     *
     * @return array<string, array{label: string, description: string, category: string}>
     */
    public static function permissionsForUnit(?string $unit = null, ?string $requestPath = null): array
    {
        $slug = \App\Enums\ForensicUnit::resolveOperationalSlug($unit);

        if ($slug === null && $requestPath !== null) {
            $path = strtolower($requestPath);
            if (str_contains($path, 'crime_scene') || str_contains($path, 'crime-scene')) {
                $slug = 'css';
            } elseif (str_contains($path, 'digital_forensics') || str_contains($path, 'digital-forensics')) {
                $slug = 'dpf';
            }
        }

        if (in_array($slug, ['css', 'dpf'], true)) {
            return array_intersect_key(
                self::AVAILABLE_PERMISSIONS,
                array_flip(self::STREAMLINED_PERMISSION_KEYS)
            );
        }

        return self::AVAILABLE_PERMISSIONS;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fullname',
        'username',
        'service_id',
        'phone_number',
        'is_staff',
        'is_head',
        'is_approved',
        'permissions',
        'unit',
        'email',
        'avatar',
        'signature',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_staff' => 'boolean',
            'is_head' => 'boolean',
            'is_approved' => 'boolean',
            'permissions' => 'array',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Resolve effective boolean permission map for this user.
     * Approved Unit Heads receive all capabilities; staff with null permissions
     * inherit default operational access until customized by their Unit Head.
     *
     * @return array<string, bool>
     */
    public function effectivePermissions(): array
    {
        $keys = array_keys(self::AVAILABLE_PERMISSIONS);
        if ($this->is_head && $this->is_approved) {
            return array_fill_keys($keys, true);
        }

        $stored = is_array($this->permissions) ? $this->permissions : null;
        $resolved = [];
        foreach ($keys as $key) {
            if ($stored === null) {
                $resolved[$key] = (bool) $this->is_approved;
            } else {
                $resolved[$key] = (bool) ($stored[$key] ?? false);
            }
        }

        return $resolved;
    }

    /**
     * Check whether the user holds a specific operational permission.
     */
    public function hasPermission(string $permission, ?string $unitOrPath = null): bool
    {
        if ($this->is_head && $this->is_approved) {
            return true;
        }
        if (!$this->is_approved) {
            return false;
        }

        if ($unitOrPath !== null && trim($unitOrPath) !== '') {
            $contextSlug = StaffAccount::resolveUnitSlugFromContext($unitOrPath);
            $staffUnitSlug = \App\Enums\ForensicUnit::resolveOperationalSlug($this->unit);
            if ($contextSlug !== null && $staffUnitSlug !== null && $contextSlug !== $staffUnitSlug) {
                return true;
            }
        }

        $map = $this->effectivePermissions();
        return (bool) ($map[$permission] ?? false);
    }

    public function staffAccount()
    {
        return $this->hasOne(StaffAccount::class, 'user_id');
    }
}
