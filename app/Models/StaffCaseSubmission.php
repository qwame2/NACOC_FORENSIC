<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class StaffCaseSubmission extends Model
{
    protected $table = 'staff_case_submissions';

    protected $fillable = [
        'submitted_by',
        'submitted_by_name',
        'unit_name',
        'case_number_preview',
        'title',
        'suspect_name',
        'priority',
        'status',
        'payload',
        'reviewed_by',
        'reviewed_by_name',
        'reviewed_at',
        'review_notes',
        'rollback_corrections',
        'rolled_back_at',
        'approved_case_number',
    ];

    protected $casts = [
        'payload' => 'array',
        'rollback_corrections' => 'array',
        'reviewed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    public function scopeForUnit($query, string $unitContext)
    {
        $u = strtolower(trim($unitContext));
        if ($u === '' || str_contains($u, 'directorate') || str_contains($u, 'forensic_head') || str_contains($u, 'forensic head')) {
            return $query;
        }

        if (str_contains($u, 'lab')) {
            return $query->where('unit_name', 'LIKE', '%Lab%');
        }
        if (str_contains($u, 'evidence') || str_contains($u, 'vault')) {
            return $query->where('unit_name', 'LIKE', '%Evidence%');
        }
        if (str_contains($u, 'digital') || str_contains($u, 'physical') || str_contains($u, 'cyber')) {
            return $query->where(function ($q) {
                $q->where('unit_name', 'LIKE', '%Digital%')
                  ->orWhere('unit_name', 'LIKE', '%Physical%');
            });
        }

        return $query->where('unit_name', 'LIKE', '%Crime%');
    }

    /**
     * Count active rolled back cases for the given unit and user context.
     */
    public static function countRollbacksForContext(?string $unitName, ?Authenticatable $user = null): int
    {
        if (!$user) {
            return 0;
        }

        $scopedUnitName = trim((string) ($unitName ?: ''));
        if ($scopedUnitName === '') {
            $scopedUnitName = 'Crime Scene Support';
        }

        try {
            $query = static::where('status', 'Rolled Back');
            if (empty($user->is_head)) {
                $query->where(function ($q) use ($user) {
                    $q->where('submitted_by', $user->id)
                      ->orWhere('submitted_by_name', $user->fullname ?? ($user->name ?? ''));
                });
            } else {
                $query->where('unit_name', 'LIKE', '%' . $scopedUnitName . '%');
            }

            return $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Extract structured meta information for displaying this submission in queue cards and tables.
     */
    public function getSubmissionMeta(): array
    {
        $photoResolver = app(\App\Services\CasePhotoResolver::class);
        $p = is_array($this->payload) ? $this->payload : [];
        $docket = is_array($p['docket_data'] ?? null) ? $p['docket_data'] : [];

        // Raw list of suspects from payload or docket
        $rawSuspects = [];
        if (!empty($p['suspects']) && is_array($p['suspects'])) {
            $rawSuspects = array_values($p['suspects']);
        } elseif (!empty($docket['suspects']) && is_array($docket['suspects'])) {
            $rawSuspects = array_values($docket['suspects']);
        }

        // Check if additional co-suspects are listed in co_suspects or flat fields
        if (!empty($p['co_suspects']) && is_array($p['co_suspects'])) {
            foreach ($p['co_suspects'] as $coS) {
                if (is_array($coS) && (!empty($coS['suspect_name']) || !empty($coS['name']))) {
                    $rawSuspects[] = $coS;
                }
            }
        }
        if (count($rawSuspects) < 2 && (!empty($p['co_suspect_name']) || !empty($p['suspect_name_2']))) {
            $rawSuspects[] = [
                'suspect_name'  => $p['co_suspect_name'] ?? ($p['suspect_name_2'] ?? 'Co-Suspect'),
                'mugshot_front' => $p['co_suspect_mugshot_front'] ?? ($p['mugshot_front_2'] ?? ($p['co_mugshot_front'] ?? null)),
                'role'          => 'Co-Suspect',
                'is_co_suspect' => true,
            ];
        }

        // If fewer than 2 suspects found, check database records if linked
        $refCaseNo = $this->approved_case_number ?? ($this->case_number_preview ?? ($p['case_number'] ?? null));
        if ($refCaseNo && count($rawSuspects) < 2) {
            try {
                $multiDbCase = \App\Models\MultipleSuspectCase::where('case_number', $refCaseNo)->first();
                if ($multiDbCase && isset($multiDbCase->suspects) && count($multiDbCase->suspects) >= 2) {
                    $rawSuspects = [];
                    foreach ($multiDbCase->suspects as $mSp) {
                        $rawSuspects[] = [
                            'suspect_name'  => $mSp->suspect_name,
                            'mugshot_front' => $mSp->mugshot_front ?? ($mSp->photo ?? null),
                            'role'          => $mSp->role ?? 'Co-Suspect',
                        ];
                    }
                }
                if (count($rawSuspects) < 2) {
                    $fCase = \App\Models\ForensicCase::where('case_number', $refCaseNo)->first();
                    if ($fCase && method_exists($fCase, 'getSuspectsList')) {
                        $fList = $fCase->getSuspectsList();
                        if (count($fList) >= 2) {
                            $rawSuspects = $fList;
                        }
                    }
                }
            } catch (\Throwable) {}
        }

        // Fallback: if rawSuspects is still empty but suspect_name exists
        if (empty($rawSuspects) && !empty($this->suspect_name)) {
            $rawSuspects = [
                [
                    'suspect_name'  => $this->suspect_name,
                    'mugshot_front' => $p['mugshot_front'] ?? ($docket['mugshot_front'] ?? null),
                    'role'          => 'Primary Suspect',
                ]
            ];
        }

        // Primary suspect fallback mugshot resolution
        $rawFront1 = $p['mugshot_front']
            ?? ($p['mugshot'] ?? null)
            ?? ($p['mugshots']['front'] ?? null)
            ?? ($p['photo'] ?? null)
            ?? ($p['suspect_photo'] ?? null)
            ?? ($rawSuspects[0]['mugshot_front'] ?? null)
            ?? ($rawSuspects[0]['mugshot'] ?? null)
            ?? ($rawSuspects[0]['mugshots']['front'] ?? null)
            ?? ($rawSuspects[0]['photo'] ?? null)
            ?? ($rawSuspects[0]['suspect_photo'] ?? null)
            ?? ($docket['mugshot_front'] ?? null)
            ?? ($docket['mugshots']['front'] ?? null);

        if (empty($rawFront1) && !empty($this->approved_case_number)) {
            try {
                $approvedRecord = \App\Models\SingleSuspectCase::where('case_number', $this->approved_case_number)->first()
                    ?? \App\Models\MultipleSuspectCase::where('case_number', $this->approved_case_number)->first();
                if ($approvedRecord) {
                    if ($approvedRecord instanceof \App\Models\SingleSuspectCase) {
                        $rawFront1 = $approvedRecord->mugshot_front;
                    } elseif (isset($approvedRecord->suspects) && count($approvedRecord->suspects) > 0) {
                        $rawFront1 = $approvedRecord->suspects[0]->mugshot_front ?? null;
                    }
                }
                if (empty($rawFront1)) {
                    $fCase = \App\Models\ForensicCase::where('case_number', $this->approved_case_number)->first();
                    if ($fCase) {
                        $rawFront1 = $fCase->mugshot_front
                            ?? ($fCase->suspects[0]->mugshot_front ?? null)
                            ?? ($fCase->suspects[0]['mugshot_front'] ?? null);
                    }
                }
            } catch (\Throwable) {}
        }

        // Format each suspect in the list
        $resolvedSuspects = [];
        foreach ($rawSuspects as $idx => $sp) {
            $spName = is_array($sp)
                ? ($sp['suspect_name'] ?? ($sp['name'] ?? ($sp['full_name'] ?? null)))
                : ($sp->suspect_name ?? ($sp->name ?? ($sp->full_name ?? null)));

            if (!$spName && $idx === 0) {
                $spName = $this->suspect_name ?: 'Primary Suspect';
            }

            $frontRaw = is_array($sp)
                ? ($sp['mugshot_front']
                    ?? ($sp['photo'] ?? null)
                    ?? ($sp['mugshot'] ?? null)
                    ?? ($sp['mugshots']['front'] ?? null)
                    ?? ($sp['mugshots']['front-profile'] ?? null)
                    ?? ($sp['suspect_photo'] ?? null))
                : ($sp->mugshot_front
                    ?? ($sp->photo ?? null)
                    ?? ($sp->mugshot ?? null)
                    ?? ($sp->suspect_photo ?? null));

            if (empty($frontRaw) && $idx === 0) {
                $frontRaw = $rawFront1;
            }
            if (empty($frontRaw) && $idx === 1) {
                $frontRaw = $p['mugshot_front_2']
                    ?? ($p['co_suspect_mugshot_front'] ?? null)
                    ?? ($p['co_mugshot_front'] ?? null)
                    ?? ($docket['mugshot_front_2'] ?? null);
            }

            $frontUrl = $frontRaw ? $photoResolver->formatMugshotUrl($frontRaw) : null;
            $role = is_array($sp)
                ? ($sp['association_role'] ?? ($sp['role'] ?? ($idx === 0 ? 'Primary Suspect' : 'Co-Suspect')))
                : ($sp->association_role ?? ($sp->role ?? ($idx === 0 ? 'Primary Suspect' : 'Co-Suspect')));

            $resolvedSuspects[] = [
                'index'       => $idx + 1,
                'name'        => $spName ?: "Suspect " . ($idx + 1),
                'role'        => $role,
                'mugshot_url' => $frontUrl,
                'raw_front'   => $frontRaw,
            ];
        }

        $isMulti = count($resolvedSuspects) >= 2
            || ($p['suspect_docket_mode'] ?? '') === 'MULTIPLE'
            || ($docket['suspect_docket_mode'] ?? '') === 'MULTIPLE'
            || str_contains(strtolower((string) $this->title), 'multiple suspect')
            || str_contains(strtolower((string) $this->suspect_name), ' & ');

        $suspectCount = (count($resolvedSuspects) > 0)
            ? count($resolvedSuspects)
            : (!empty($this->suspect_name) ? 1 : 0);

        $seizuresList = $p['seizures'] ?? ($docket['seizures'] ?? ($p['exhibits'] ?? []));
        $exhibitCount = is_array($seizuresList) ? count($seizuresList) : 0;

        $offense = trim((string) ($p['offense_type'] ?? ($p['offense'] ?? ($p['operation_type'] ?? ''))));
        $location = trim((string) ($p['location'] ?? ($p['raid_location'] ?? ($p['arrest_location'] ?? ''))));
        $caseRef = trim((string) ($this->case_number_preview ?? ($p['case_number'] ?? '')));

        $mugshotUrl = $resolvedSuspects[0]['mugshot_url'] ?? $photoResolver->formatMugshotUrl($rawFront1);

        return [
            'suspect_count'        => $suspectCount,
            'exhibit_count'        => $exhibitCount,
            'offense'              => $offense,
            'location'             => $location,
            'case_ref'             => $caseRef,
            'mugshot_url'          => $mugshotUrl,
            'suspects'             => $resolvedSuspects,
            'is_multiple_suspects' => $isMulti,
        ];
    }

    /**
     * Resolve the dossier review URL for this submission based on unit context.
     */
    public function getReviewUrl(?string $unitKey = null): string
    {
        $uKey = $unitKey;
        if (!$uKey) {
            $u = strtolower((string) $this->unit_name);
            if (str_contains($u, 'lab')) $uKey = 'lab_analysis';
            elseif (str_contains($u, 'evidence') || str_contains($u, 'vault')) $uKey = 'evidence_management';
            elseif (str_contains($u, 'digital') || str_contains($u, 'physical')) $uKey = 'digital_forensics';
            elseif (str_contains($u, 'directorate') || str_contains($u, 'head')) $uKey = 'forensic_head';
            else $uKey = 'crime_scene_support';
        }

        return match ($uKey) {
            'lab_analysis' => url('/Lab_Analysis/reviews-approvals/' . $this->id),
            'evidence_management' => url('/Evidence_Management/reviews-approvals/' . $this->id),
            'digital_forensics' => url('/Digital_Forensics/reviews-approvals/' . $this->id),
            'forensic_head' => url('/Forensic_Head/reviews-approvals/' . $this->id),
            default => url('/Crime_Scene_Support/reviews-approvals/' . $this->id),
        };
    }
}

