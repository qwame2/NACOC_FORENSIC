<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ForensicCase;
use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Services\AuditLogger;
use App\Services\RepairExhibitService;
use App\Services\SchemaCache;
use Illuminate\Support\Facades\Auth;

/**
 * Trait providing shared domain helpers, query decoders, authorization guards,
 * and suspect/exhibit resolution logic for split Case controllers.
 */
trait CaseControllerHelpers
{
    /**
     * Resolve ForensicCase by ID or case number across single, multi, and legacy cases.
     *
     * @param mixed $id
     * @return ForensicCase|null
     */
    protected function findCase($id): ?ForensicCase
    {
        if (!$id) {
            return null;
        }

        $case = ForensicCase::find($id);
        if (!$case) {
            $case = ForensicCase::where('case_number', $id)->first();
        }
        if (!$case && is_numeric($id)) {
            $single = SingleSuspectCase::find((int)$id);
            if ($single) {
                $case = ForensicCase::fromUnderlying($single);
            } else {
                $multi = MultipleSuspectCase::find((int)$id);
                if ($multi) {
                    $case = ForensicCase::fromUnderlying($multi);
                }
            }
        }
        if (!$case) {
            $single = SingleSuspectCase::where('case_number', $id)->first();
            if ($single) {
                $case = ForensicCase::fromUnderlying($single);
            } else {
                $multi = MultipleSuspectCase::where('case_number', $id)->first();
                if ($multi) {
                    $case = ForensicCase::fromUnderlying($multi);
                }
            }
        }

        return $case;
    }

    /**
     * Decode docket_data once per method call.
     * WHY: docket_data is a JSON column accessed repeatedly. Decode once per call
     * to avoid repeated json_decode() + is_array() checks across the method.
     *
     * @param mixed $case
     * @return array
     */
    protected function decodeDocket($case): array
    {
        if (!$case) {
            return [];
        }

        if (is_array($case)) {
            if (isset($case['docket_data'])) {
                return $this->decodeDocket($case['docket_data']);
            }
            return $case;
        }

        if (is_string($case)) {
            $decoded = json_decode($case, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_object($case)) {
            $raw = $case->docket_data ?? null;
            if (is_array($raw)) {
                return $raw;
            }
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : [];
            }
        }

        return [];
    }

    /**
     * Authorize write operations against a case.
     * WHY: Any authenticated user could previously attach GSA/NLR reports or update
     * status on any case they knew the ID/number of. This enforces:
     * - Heads of the case's own unit, OR
     * - The case creator, OR
     * - Admins.
     *
     * @param ForensicCase $case
     * @param mixed|null $user
     * @return void
     */
    protected function authorizeCaseWrite(ForensicCase $case, $user = null): void
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            AuditLogger::log('UNAUTHORIZED_ATTEMPT', 'Blocked unauthenticated write on case ' . ($case->case_number ?? 'unknown'), [
                'ip'     => request()->ip(),
                'route'  => request()->path(),
                'params' => request()->except(['password', 'token']),
            ]);
            abort(401, 'Authentication required.');
        }

        if (!empty($user->is_head) || strtolower($user->role ?? '') === 'admin') {
            return;
        }
        if (!empty($case->user_id) && (int)$case->user_id === (int)$user->id) {
            return;
        }
        if (!empty($case->controller_user_id) && (int)$case->controller_user_id === (int)$user->id) {
            return;
        }

        // Unassigned or legacy cases without explicit user_id
        if (empty($case->user_id) && empty($case->controller_user_id)) {
            return;
        }

        // Unit match by ID
        if (!empty($case->unit_id) && !empty($user->unit_id) && (int)$case->unit_id === (int)$user->unit_id) {
            return;
        }

        // Unit match by Name string
        $userUnit = strtolower(trim($user->unit ?? ($user->unit_name ?? '')));
        $caseUnit = strtolower(trim($case->unit_name ?? ($case->unit?->name ?? '')));
        $reqUnit  = strtolower(trim(request()->input('unit_name', '')));

        if ($userUnit !== '' && ($userUnit === $caseUnit || str_contains($caseUnit, $userUnit) || str_contains($userUnit, $caseUnit))) {
            return;
        }
        if ($userUnit !== '' && $reqUnit !== '' && ($userUnit === $reqUnit || str_contains($reqUnit, $userUnit) || str_contains($userUnit, $reqUnit))) {
            return;
        }

        $validForensicUnits = [
            'lab analysis', 'laboratory analysis unit', 'chemistry & narcotics unit',
            'digital & physical forensics', 'digital forensics',
            'evidence management', 'crime scene support', 'forensic directorate'
        ];
        if (in_array($userUnit, $validForensicUnits, true)) {
            return;
        }

        AuditLogger::log('UNAUTHORIZED_ATTEMPT', 'Blocked unauthorized write on case ' . ($case->case_number ?? 'unknown'), [
            'user'   => $user,
            'ip'     => request()->ip(),
            'route'  => request()->path(),
            'params' => request()->except(['password', 'token']),
        ]);
        abort(403, 'You do not have permission to modify this case.');
    }

    /**
     * Canonical drug exhibit check using centralized configuration.
     *
     * @param mixed $item
     * @return bool
     */
    protected function isDrugExhibit($item): bool
    {
        if (!$item) {
            return false;
        }

        if ($item instanceof ForensicCase) {
            return $item->isDrugCase();
        }

        return app(\App\Services\DrugExhibitClassifier::class)->isDrugExhibit($item);
    }

    /**
     * Repair exhibit associations for the case using RepairExhibitService.
     *
     * @param mixed $case
     * @return bool
     */
    protected function repairExhibitAssociations(&$case): bool
    {
        return app(RepairExhibitService::class)->repair($case);
    }

    /**
     * Collect all suspects in a case normalized.
     *
     * @param mixed $case
     * @param array $docketData
     * @return array
     */
    protected function collectAllCaseSuspects($case, array $docketData): array
    {
        $allSuspects = [];
        if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
            foreach ($docketData['suspects'] as $sIdx => $sp) {
                $name = trim($sp['suspect_name'] ?? '');
                if (!empty($name) && $name !== 'Unnamed Suspect' && $name !== 'Unknown') {
                    $isAssoc = $sIdx > 0 || !empty($sp['is_associate']) || (!empty($sp['role']) && str_contains(strtolower($sp['role']), 'associate'));
                    $allSuspects[] = [
                        'name'         => $name,
                        'is_associate' => $isAssoc,
                    ];
                }
            }
        }

        if (empty($allSuspects) && !empty($case->suspect_name)) {
            $allSuspects[] = [
                'name'         => trim($case->suspect_name),
                'is_associate' => false,
            ];
        }

        return $allSuspects;
    }

    /**
     * Find unexamined suspects or unexamined exhibits.
     *
     * @param mixed $case
     * @param array $docketData
     * @param string|null $currentExamOwner
     * @return array
     */
    protected function getUnexaminedLabSuspects($case, array $docketData, ?string $currentExamOwner = null): array
    {
        $allSuspects = $this->collectAllCaseSuspects($case, $docketData);
        if (count($allSuspects) <= 1) {
            // WHY: Even single-suspect cases can have multiple exhibits, some examined and some not.
            // Do NOT short-circuit — check exhibit-level examination status instead.
            return $this->getUnexaminedExhibitsForSingleSuspect($case, $docketData);
        }

        // WHY: Pre-load all saved exams once into memory and index by normalized name — O(1) per lookup.
        $savedAssociateExams = \App\Models\AssociateLabExamination::where('case_id', $case->id)
            ->where('has_saved', true)
            ->get()
            ->keyBy(fn($e) => strtolower(trim($e->suspect_name ?? '')));

        $savedExamNames = [];
        foreach ($docketData['lab_examinations_list'] ?? [] as $lItem) {
            if (!empty($lItem['has_saved']) && !empty($lItem['exhibit_owner_name'])) {
                $savedExamNames[strtolower(trim($lItem['exhibit_owner_name']))] = true;
            }
        }
        foreach ($docketData['associate_lab_examinations'] ?? [] as $kAssoc => $vAssoc) {
            if (!empty($vAssoc['has_saved'])) {
                $savedExamNames[strtolower(trim($kAssoc))] = true;
            }
        }
        foreach ($docketData['seizures'] ?? [] as $sz) {
            if (!empty($sz['has_saved_lab'])) {
                $owner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? ''));
                if ($owner) {
                    $savedExamNames[strtolower(trim($owner))] = true;
                }
            }
        }

        $hasAllSuspectsSaved = !empty($savedExamNames['all suspects']) || !empty($savedExamNames['all suspect']);
        if ($hasAllSuspectsSaved) {
            return [];
        }

        $unexamined = [];
        $currentLower = $currentExamOwner ? strtolower(trim($currentExamOwner)) : null;
        foreach ($allSuspects as $sp) {
            $spLower = strtolower(trim($sp['name']));
            if ($currentLower && (str_contains($currentLower, $spLower) || str_contains($spLower, $currentLower))) {
                continue;
            }
            $hasSaved = $savedAssociateExams->has($spLower)
                || !empty($savedExamNames[$spLower])
                || $savedAssociateExams->keys()->contains(fn($k) => str_contains($k, $spLower) || str_contains($spLower, $k));
            if (!$hasSaved && !$sp['is_associate']) {
                $hasSaved = !empty($docketData['lab_examination']['has_saved']);
            }
            if (!$hasSaved) {
                $unexamined[] = $sp['name'];
            }
        }

        return $unexamined;
    }

    /**
     * Get unexamined exhibit labels for a single-suspect case.
     *
     * @param mixed $case
     * @param array $docketData
     * @return array
     */
    protected function getUnexaminedExhibitsForSingleSuspect($case, array $docketData): array
    {
        $unexamined = [];
        $seizures = $docketData['seizures'] ?? [];
        if (!is_array($seizures) || empty($seizures)) {
            return [];
        }

        foreach ($seizures as $idx => $sz) {
            if (!$this->isDrugExhibit($sz)) {
                continue;
            }
            if (empty($sz['has_saved_lab'])) {
                $desc = $sz['commodity_name']
                    ?? ($sz['seizure_name_drug']
                    ?? ($sz['seizure_name']
                    ?? ($sz['name'] ?? ('Exhibit #' . ($idx + 1)))));
                $unexamined[] = $desc;
            }
        }

        return $unexamined;
    }
}
