<?php

namespace App\Services;

use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\AssociateLabExamination;
use Illuminate\Support\Carbon;

class CaseDuplicateDetector
{
    /**
     * Normalize string input (lowercase, trim extra spaces, remove punctuation).
     */
    public function normalizeString(?string $val): string
    {
        if (empty($val)) return '';
        $clean = strtolower(trim($val));
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean);
    }

    /**
     * Normalize Ghana Card / National ID (uppercase, strip spaces/hyphens).
     */
    public function normalizeGhanaCard(?string $val): string
    {
        if (empty($val)) return '';
        $clean = strtoupper(trim($val));
        return preg_replace('/[^A-Z0-9]/', '', $clean);
    }

    /**
     * Normalize Date format to YYYY-MM-DD.
     */
    public function normalizeDate(?string $val): ?string
    {
        if (empty($val) || $val === 'N/A' || $val === 'null') return null;
        try {
            return Carbon::parse(trim($val))->format('Y-m-d');
        } catch (\Throwable $e) {
            return substr(trim($val), 0, 10);
        }
    }

    /**
     * Normalize phone number (digits only).
     */
    public function normalizePhone(?string $val): string
    {
        if (empty($val)) return '';
        return preg_replace('/[^0-9]/', '', $val);
    }

    /**
     * Compare suspect biodata against existing records in database.
     * Returns matching score, criteria reasons, and matching suspect details.
     */
    public function checkSuspectBiodata(array $suspect): array
    {
        $inputName  = $this->normalizeString($suspect['suspect_name'] ?? '');
        $inputCard  = $this->normalizeGhanaCard($suspect['ghana_card'] ?? null);
        $inputDob   = $this->normalizeDate($suspect['dob'] ?? ($suspect['date_of_birth'] ?? null));
        $inputGender= $this->normalizeString($suspect['gender'] ?? null);
        $inputNat   = $this->normalizeString($suspect['nationality'] ?? null);
        $inputPhone = $this->normalizePhone($suspect['phone'] ?? ($suspect['phone_number'] ?? ($suspect['contact_phone'] ?? null)));
        $inputAddr  = $this->normalizeString($suspect['address'] ?? ($suspect['residential_address'] ?? null));

        if (empty($inputName) && empty($inputCard)) {
            return ['matched' => false, 'score' => 0, 'matching_suspect' => null, 'reasons' => []];
        }

        $allCases = ForensicCase::all();
        $bestMatch = null;
        $maxScore = 0;
        $matchReasons = [];

        foreach ($allCases as $case) {
            $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            $suspectsList = $docketData['suspects'] ?? [];

            if (empty($suspectsList)) {
                $suspectsList = [[
                    'suspect_name' => $case->suspect_name,
                    'ghana_card'   => $case->ghana_card,
                    'dob'          => $case->dob,
                    'gender'       => $case->gender,
                    'nationality'  => $case->nationality,
                    'phone'        => $case->phone,
                    'address'      => $case->address,
                ]];
            }

            foreach ($suspectsList as $dbSp) {
                $dbName   = $this->normalizeString($dbSp['suspect_name'] ?? '');
                $dbCard   = $this->normalizeGhanaCard($dbSp['ghana_card'] ?? null);
                $dbDob    = $this->normalizeDate($dbSp['dob'] ?? null);
                $dbGender = $this->normalizeString($dbSp['gender'] ?? null);
                $dbNat    = $this->normalizeString($dbSp['nationality'] ?? null);
                $dbPhone  = $this->normalizePhone($dbSp['phone'] ?? ($dbSp['phone_number'] ?? null));
                $dbAddr   = $this->normalizeString($dbSp['address'] ?? null);

                $score = 0;
                $reasons = [];

                // 1. Ghana Card match (Very Strong: 90 pts)
                if (!empty($inputCard) && !empty($dbCard) && $inputCard === $dbCard) {
                    $score += 90;
                    $reasons[] = "Matching National ID / Ghana Card Number ($inputCard)";
                }

                // 2. Name Match evaluation
                $namesMatch = false;
                if (!empty($inputName) && !empty($dbName)) {
                    if ($inputName === $dbName || str_contains($inputName, $dbName) || str_contains($dbName, $inputName)) {
                        $namesMatch = true;
                    } else {
                        // Check component words match
                        $w1 = array_filter(explode(' ', $inputName), fn($w) => strlen($w) >= 2);
                        $w2 = array_filter(explode(' ', $dbName), fn($w) => strlen($w) >= 2);
                        $intersect = array_intersect($w1, $w2);
                        if (count($intersect) >= 2 && count($intersect) === max(count($w1), count($w2))) {
                            $namesMatch = true;
                        }
                    }
                }

                // 3. Name + DOB (Strong Match: 80 pts)
                if ($namesMatch && !empty($inputDob) && !empty($dbDob) && $inputDob === $dbDob) {
                    $score += 80;
                    $reasons[] = "Matching Full Name ($inputName) and Date of Birth ($inputDob)";

                    if (!empty($inputGender) && !empty($dbGender) && $inputGender === $dbGender) {
                        $score += 10;
                        $reasons[] = "Matching Gender (" . strtoupper($inputGender) . ")";
                    }
                    if (!empty($inputNat) && !empty($dbNat) && $inputNat === $dbNat) {
                        $score += 5;
                        $reasons[] = "Matching Nationality (" . strtoupper($inputNat) . ")";
                    }
                } elseif ($namesMatch && !empty($inputPhone) && !empty($dbPhone) && $inputPhone === $dbPhone) {
                    // Name + Phone Match (70 pts)
                    $score += 70;
                    $reasons[] = "Matching Full Name ($inputName) and Phone Number ($inputPhone)";
                } elseif ($namesMatch && !empty($inputDob) && !empty($dbDob) && substr($inputDob, 0, 4) === substr($dbDob, 0, 4)) {
                    // Name + Birth Year (60 pts)
                    $score += 60;
                    $reasons[] = "Matching Full Name ($inputName) and Birth Year (" . substr($inputDob, 0, 4) . ")";
                } elseif ($namesMatch) {
                    // Name only (30 pts - NOT enough alone for suspect duplicate)
                    $score += 30;
                }

                if ($score > $maxScore) {
                    $maxScore = $score;
                    $matchReasons = $reasons;
                    $bestMatch = [
                        'case_number'      => $case->case_number,
                        'suspect_name'     => $dbSp['suspect_name'] ?? $case->suspect_name,
                        'ghana_card'       => $dbSp['ghana_card'] ?? $case->ghana_card,
                        'dob'              => $dbSp['dob'] ?? $case->dob,
                        'gender'           => $dbSp['gender'] ?? $case->gender,
                        'nationality'      => $dbSp['nationality'] ?? $case->nationality,
                        'phone'            => $dbSp['phone'] ?? $case->phone,
                        'incident_date'    => $case->incident_date ? substr((string)$case->incident_date, 0, 10) : null,
                        'raid_location'    => $case->raid_location ?: ($case->location ?: 'N/A'),
                        'exhibit_category' => $case->exhibit_category ?? 'N/A',
                        'status'           => $case->status ?? 'Approved',
                    ];
                }
            }
        }

        // Threshold for suspect match = 65
        return [
            'matched'          => $maxScore >= 65,
            'score'            => $maxScore,
            'matching_suspect' => $maxScore >= 65 ? $bestMatch : null,
            'reasons'          => $matchReasons,
        ];
    }

    /**
     * Check if an entire case payload constitutes a duplicate case.
     * Evaluates suspect biodata + case information + multi-suspect group composition.
     */
    public function checkCaseDuplicate(array $caseData, array $suspectsData): array
    {
        if (empty($suspectsData)) {
            $sName = trim($caseData['suspect_name'] ?? '');
            if (!empty($sName) && $sName !== 'Unnamed Suspect' && $sName !== 'Unknown') {
                $suspectsData = [[
                    'suspect_name' => $sName,
                    'ghana_card'   => $caseData['ghana_card'] ?? null,
                    'dob'          => $caseData['dob'] ?? null,
                    'gender'       => $caseData['gender'] ?? null,
                    'nationality'  => $caseData['nationality'] ?? null,
                    'phone'        => $caseData['phone'] ?? null,
                    'address'      => $caseData['address'] ?? null,
                ]];
            }
        }

        $inputIncidentDate = $this->normalizeDate($caseData['incident_date'] ?? ($caseData['arrest_date'] ?? ($caseData['seizure_date'] ?? null)));
        $inputLocation     = $this->normalizeString($caseData['raid_location'] ?? ($caseData['location'] ?? ($caseData['seizure_location'] ?? null)));
        $inputCategory     = $this->normalizeString($caseData['exhibit_category'] ?? null);
        $inputCommodity    = $this->normalizeString($caseData['seizure_name_drug'] ?? ($caseData['seizure_name'] ?? null));
        $inputOperation    = $this->normalizeString($caseData['operation_code'] ?? ($caseData['operation_code_name'] ?? null));

        // Normalize input suspect names array
        $inputSuspectNames = array_values(array_filter(array_map(fn($s) => $this->normalizeString($s['suspect_name'] ?? ''), $suspectsData)));

        $allCases = ForensicCase::all();

        $highestScore = 0;
        $bestMatchingCase = null;
        $detectionLevel = 'NONE';
        $matchingReasons = [];

        foreach ($allCases as $case) {
            $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            $dbSuspects = $docketData['suspects'] ?? [];
            if (empty($dbSuspects)) {
                $dbSuspects = [[
                    'suspect_name' => $case->suspect_name,
                    'ghana_card'   => $case->ghana_card,
                    'dob'          => $case->dob,
                    'gender'       => $case->gender,
                    'nationality'  => $case->nationality,
                    'phone'        => $case->phone,
                    'address'      => $case->address,
                ]];
            }

            $dbSuspectNames = array_values(array_filter(array_map(fn($s) => $this->normalizeString($s['suspect_name'] ?? ''), $dbSuspects)));

            // Evaluate suspect biodata matches for this case
            $suspectMatchCount = 0;
            $suspectScores = [];
            $suspectReasonsList = [];

            foreach ($suspectsData as $inSp) {
                $spRes = $this->checkSuspectBiodataForSingleCase($inSp, $dbSuspects, $case);
                if ($spRes['score'] >= 65) {
                    $suspectMatchCount++;
                    $suspectScores[] = $spRes['score'];
                    $suspectReasonsList = array_merge($suspectReasonsList, $spRes['reasons']);
                }
            }

            if (empty($suspectScores)) {
                continue; // No suspect biodata matches at all for this case
            }

            // Evaluate Multi-Suspect Group Composition
            $isMultiGroupMatch = false;
            if (count($inputSuspectNames) > 1 && count($dbSuspectNames) > 1) {
                $intersection = array_intersect($inputSuspectNames, $dbSuspectNames);
                if (count($intersection) >= min(count($inputSuspectNames), count($dbSuspectNames))) {
                    $isMultiGroupMatch = true;
                }
            } elseif (count($inputSuspectNames) === 1 && count($dbSuspectNames) === 1) {
                if ($suspectMatchCount > 0) {
                    $isMultiGroupMatch = true;
                }
            }

            // Case Information Evaluation
            $dbDate      = $this->normalizeDate($case->incident_date ? (string)$case->incident_date : ($case->arrest_date ? (string)$case->arrest_date : ($case->seizure_date ? (string)$case->seizure_date : null)));
            $dbLocation  = $this->normalizeString($case->raid_location ?: ($case->location ?: ''));
            $dbCategory  = $this->normalizeString($case->exhibit_category ?? '');
            $dbCommodity = $this->normalizeString($case->seizure_name_drug ?: ($case->seizure_name ?: ''));
            $dbOperation = $this->normalizeString($case->operation_code ?: ($case->operation_code_name ?: ''));

            $caseMatchScore = 0;
            $caseReasons = [];

            // Incident Date match
            if (!empty($inputIncidentDate) && !empty($dbDate) && $inputIncidentDate === $dbDate) {
                $caseMatchScore += 35;
                $caseReasons[] = "Identical Incident / Arrest Date ($inputIncidentDate)";
            }

            // Location match
            if (!empty($inputLocation) && !empty($dbLocation) && ($inputLocation === $dbLocation || str_contains($inputLocation, $dbLocation) || str_contains($dbLocation, $inputLocation))) {
                $caseMatchScore += 25;
                $caseReasons[] = "Matching Incident / Raid Location ('" . strtoupper($inputLocation) . "')";
            }

            // Category & Commodity match
            if (!empty($inputCategory) && !empty($dbCategory) && $inputCategory === $dbCategory) {
                $caseMatchScore += 15;
                if (!empty($inputCommodity) && !empty($dbCommodity) && ($inputCommodity === $dbCommodity || str_contains($inputCommodity, $dbCommodity) || str_contains($dbCommodity, $inputCommodity))) {
                    $caseMatchScore += 15;
                    $caseReasons[] = "Matching Exhibit / Commodity ('" . strtoupper($inputCommodity) . "')";
                }
            }

            // Operation code match
            if (!empty($inputOperation) && !empty($dbOperation) && $inputOperation === $dbOperation) {
                $caseMatchScore += 20;
                $caseReasons[] = "Matching Operation Code / Name ('" . strtoupper($inputOperation) . "')";
            }

            $avgSuspectScore = array_sum($suspectScores) / count($suspectScores);
            $totalCombinedScore = (int) ($avgSuspectScore * 0.55 + $caseMatchScore * 0.45);

            // High Confidence duplicate requirement:
            // High suspect score AND significant case info match (or exact multi-suspect group + same date/location)
            if (($totalCombinedScore >= 75 || ($avgSuspectScore >= 80 && $caseMatchScore >= 25)) && $isMultiGroupMatch) {
                $level = 'HIGH_CONFIDENCE';
            } elseif ($totalCombinedScore >= 50 && ($caseMatchScore >= 15 || $avgSuspectScore >= 70)) {
                $level = 'POTENTIAL';
            } else {
                $level = 'NONE';
            }

            if ($totalCombinedScore > $highestScore) {
                $highestScore = $totalCombinedScore;
                $detectionLevel = $level;
                $matchingReasons = array_unique(array_merge($suspectReasonsList, $caseReasons));

                $primaryDbSp = $dbSuspects[0] ?? [];
                $bestMatchingCase = [
                    'case_number'      => $case->case_number,
                    'title'            => $case->title,
                    'suspect_name'     => implode(', ', array_filter(array_map(fn($s) => $s['suspect_name'] ?? null, $dbSuspects))) ?: $case->suspect_name,
                    'dob'              => $primaryDbSp['dob'] ?? $case->dob,
                    'ghana_card'       => $primaryDbSp['ghana_card'] ?? $case->ghana_card,
                    'incident_date'    => $dbDate ?: 'N/A',
                    'location'         => $case->raid_location ?: ($case->location ?: 'N/A'),
                    'exhibit_category' => $case->exhibit_category ?? 'N/A',
                    'status'           => $case->status ?? 'Approved',
                    'created_at'       => is_object($case->created_at) ? $case->created_at->format('Y-m-d H:i') : (string)$case->created_at,
                ];
            }
        }

        if ($detectionLevel === 'HIGH_CONFIDENCE') {
            $msg = "Possible Duplicate Case Found: This case appears to match an existing registered case file ({$bestMatchingCase['case_number']}). Please review the existing record before registering this case.";
        } elseif ($detectionLevel === 'POTENTIAL') {
            $msg = "Potential Duplicate Detected: Some information entered matches an existing case record ({$bestMatchingCase['case_number']}). Please review the record before continuing.";
        } else {
            $msg = "No duplicate case detected.";
        }

        return [
            'duplicate_detected' => $detectionLevel !== 'NONE',
            'level'              => $detectionLevel,
            'score'              => $highestScore,
            'message'            => $msg,
            'reasons'            => array_values($matchingReasons),
            'existing_case'      => $bestMatchingCase,
        ];
    }

    /**
     * Helper to check suspect biodata against a specific case's suspect list.
     */
    private function checkSuspectBiodataForSingleCase(array $inSp, array $dbSuspects, ForensicCase $case): array
    {
        $inputName  = $this->normalizeString($inSp['suspect_name'] ?? '');
        $inputCard  = $this->normalizeGhanaCard($inSp['ghana_card'] ?? null);
        $inputDob   = $this->normalizeDate($inSp['dob'] ?? ($inSp['date_of_birth'] ?? null));
        $inputGender= $this->normalizeString($inSp['gender'] ?? null);
        $inputNat   = $this->normalizeString($inSp['nationality'] ?? null);
        $inputPhone = $this->normalizePhone($inSp['phone'] ?? ($inSp['phone_number'] ?? null));

        $maxScore = 0;
        $reasons = [];

        foreach ($dbSuspects as $dbSp) {
            $dbName   = $this->normalizeString($dbSp['suspect_name'] ?? '');
            $dbCard   = $this->normalizeGhanaCard($dbSp['ghana_card'] ?? null);
            $dbDob    = $this->normalizeDate($dbSp['dob'] ?? null);
            $dbGender = $this->normalizeString($dbSp['gender'] ?? null);
            $dbNat    = $this->normalizeString($dbSp['nationality'] ?? null);
            $dbPhone  = $this->normalizePhone($dbSp['phone'] ?? null);

            $score = 0;
            $r = [];

            if (!empty($inputCard) && !empty($dbCard) && $inputCard === $dbCard) {
                $score += 90;
                $r[] = "Matching National ID / Ghana Card Number ($inputCard)";
            }

            $namesMatch = false;
            if (!empty($inputName) && !empty($dbName)) {
                if ($inputName === $dbName || str_contains($inputName, $dbName) || str_contains($dbName, $inputName)) {
                    $namesMatch = true;
                }
            }

            if ($namesMatch && !empty($inputDob) && !empty($dbDob) && $inputDob === $dbDob) {
                $score += 80;
                $r[] = "Matching Full Name ($inputName) and Date of Birth ($inputDob)";

                if (!empty($inputGender) && !empty($dbGender) && $inputGender === $dbGender) {
                    $score += 10;
                    $r[] = "Matching Gender (" . strtoupper($inputGender) . ")";
                }
                if (!empty($inputNat) && !empty($dbNat) && $inputNat === $dbNat) {
                    $score += 5;
                    $r[] = "Matching Nationality (" . strtoupper($inputNat) . ")";
                }
            } elseif ($namesMatch && !empty($inputPhone) && !empty($dbPhone) && $inputPhone === $dbPhone) {
                $score += 70;
                $r[] = "Matching Full Name ($inputName) and Phone Number ($inputPhone)";
            } elseif ($namesMatch) {
                $score += 30;
            }

            if ($score > $maxScore) {
                $maxScore = $score;
                $reasons = $r;
            }
        }

        return ['score' => $maxScore, 'reasons' => $reasons];
    }
}
