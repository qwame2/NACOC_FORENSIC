<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'view_cases' => [
            'label'       => 'View Case Directory',
            'description' => 'Read case registers, dossiers, and docket timelines',
            'category'    => 'Cases',
        ],
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
    public function hasPermission(string $permission): bool
    {
        if ($this->is_head && $this->is_approved) {
            return true;
        }
        if (!$this->is_approved) {
            return false;
        }

        $map = $this->effectivePermissions();
        return (bool) ($map[$permission] ?? false);
    }
}
