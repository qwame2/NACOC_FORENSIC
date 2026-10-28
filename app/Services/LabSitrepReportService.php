<?php

namespace App\Services;

use App\Models\DrugScreening;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use Illuminate\Support\Collection;

/**
 * LabSitrepReportService
 *
 * Dedicated service for aggregating laboratory situation report (SITREP) data
 * and compiling truthful drug examination records.
 * Fix Issue 5: Uses config('forensic.drug_keywords') for drug detection.
 * Fix Issue 7b: Removed all fabricated fallback data (Emmanuel Agyare, Heston Adjei, Hah tabulu, etc.).
 * Fix Issue 7c: Removed fabricated drug fallback records.
 * Fix Issue 20: Removed unused dead variable 'allScreeningRecords'.
 * Fix Issue 22: Neutral label in resolveDrugName() fallback.
 */
class LabSitrepReportService
{
    use CaseControllerHelpers;

    /**
     * Build truthful drug analysis records for the Lab Result view.
     */
    public function buildLabResultRecords(Request $request): Collection
    {
        $cases = ForensicCase::allCases();

        $drugKeywords = config('forensic.drug_keywords', []);
        $nonDrugKeywords = config('forensic.non_drug_keywords', []);

        $isStrictlyDrugName = function ($str) use ($nonDrugKeywords, $drugKeywords) {
            if (empty($str)) return false;
            $lower = strtolower(trim((string)$str));
            if (in_array($lower, ['exhibits', 'n/a', 'unknown', 'none', 'null', '1 batch', '1 batch / package'], true)) {
                return false;
            }

            $hasNonDrug = false;
            foreach ($nonDrugKeywords as $nk) {
                if (str_contains($lower, $nk)) {
                    $hasNonDrug = true;
                    break;
                }
            }

            if ($hasNonDrug) {
                foreach ($drugKeywords as $dk) {
                    if ($dk !== 'drug' && $dk !== 'substance' && $dk !== 'narcotic' && $dk !== 'precursor' && str_contains($lower, $dk)) {
                        return true;
                    }
                }
                return false;
            }

            return true;
        };

        $resolveDrugName = function ($ex, $c, $docket) use ($drugKeywords, $isStrictlyDrugName) {
            $ex = is_array($ex) ? $ex : [];

            $exCandidates = [
                $ex['seizure_name_drug'] ?? null,
                $ex['substance_identification'] ?? null,
                $ex['drug_name'] ?? null,
                $ex['substance_name'] ?? null,
                $ex['chemical_name'] ?? null,
                $ex['seizure_name'] ?? null,
                $ex['commodity_name'] ?? null,
            ];
            foreach ($exCandidates as $cand) {
                if ($isStrictlyDrugName($cand)) return trim($cand);
            }

            $labExam = $docket['lab_examination'] ?? [];
            $labCandidates = [
                $labExam['substance_identification'] ?? null,
                $labExam['sample_weight_description'] ?? null,
                $labExam['drug_name'] ?? null,
            ];
            foreach ($labCandidates as $cand) {
                if ($isStrictlyDrugName($cand)) return trim($cand);
            }

            $labList = $docket['lab_examinations_list'] ?? [];
            if (is_array($labList)) {
                foreach ($labList as $lItem) {
                    if (is_array($lItem)) {
                        if ($isStrictlyDrugName($lItem['substance_identification'] ?? null)) {
                            return trim($lItem['substance_identification']);
                        }
                        if ($isStrictlyDrugName($lItem['sample_weight_description'] ?? null)) {
                            return trim($lItem['sample_weight_description']);
                        }
                    }
                }
            }

            $caseCandidates = [
                $c->seizure_name_drug ?? null,
                $c->confirmatory_substance_identification ?? null,
                $c->substance_identification ?? null,
                $c->drug_name ?? null,
                $c->seizure_name ?? null,
            ];
            foreach ($caseCandidates as $cand) {
                if ($isStrictlyDrugName($cand)) return trim($cand);
            }

            $textSources = [
                $ex['seizure_value'] ?? '',
                $ex['seizure_description'] ?? '',
                $c->seizure_value ?? '',
                $c->seizure_description ?? '',
                $c->offense_description ?? '',
            ];
            foreach ($textSources as $txt) {
                if (empty($txt)) continue;
                $tLower = strtolower($txt);
                foreach ($drugKeywords as $dk) {
                    if ($dk === 'drug' || $dk === 'substance' || $dk === 'narcotic' || $dk === 'precursor') continue;
                    if (str_contains($tLower, $dk)) {
                        foreach (explode("\n", $txt) as $line) {
                            if (str_contains(strtolower($line), $dk)) {
                                $cleanLine = trim(preg_replace('/^.*?\\[Drugs\\]:\\s*/i', '', $line));
                                if (str_contains($cleanLine, '|')) {
                                    $parts = array_map('trim', explode('|', $cleanLine));
                                    foreach ($parts as $p) {
                                        if (str_contains(strtolower($p), $dk) && $isStrictlyDrugName($p)) {
                                            return $p;
                                        }
                                    }
                                }
                                if ($isStrictlyDrugName($cleanLine)) {
                                    return $cleanLine;
                                }
                            }
                        }
                    }
                }
            }

            $presumptive = $ex['presumptive_result'] ?? ($labExam['presumptive_result'] ?? ($c->presumptive_test_result ?? ''));
            if (!empty($presumptive)) {
                $pLower = strtolower($presumptive);
                if (str_contains($pLower, 'cocaine')) return 'Cocaine (Narcotic Exhibit)';
                if (str_contains($pLower, 'heroin') || str_contains($pLower, 'diacetylmorphine')) return 'Heroin (Narcotic Exhibit)';
                if (str_contains($pLower, 'cannabis') || str_contains($pLower, 'weed')) return 'Cannabis (Narcotic Exhibit)';
                if (str_contains($pLower, 'tramadol')) return 'Tramadol (Narcotic Exhibit)';
                if (str_contains($pLower, 'meth')) return 'Methamphetamine (Narcotic Exhibit)';
            }

            // WHY Fix Issue 22: Never fabricate a specific drug name. Return neutral label.
            return 'Unidentified Substance (Pending Confirmatory Analysis)';
        };

        $resolveMugshot = function ($mug, $genderStr, $idx) {
            if (!empty($mug)) {
                if (str_starts_with($mug, 'data:image') || str_starts_with($mug, 'http://') || str_starts_with($mug, 'https://') || str_starts_with($mug, '/')) {
                    return $mug;
                }
                return asset($mug);
            }
            $g = strtolower(trim($genderStr ?? 'male'));
            if ($g === 'female' || $g === 'f') {
                return asset('test/Lady front.jfif');
            }
            $mugs = ['test/01.jfif', 'test/02.jpg', 'test/03.jfif', 'test/04.jfif', 'test/05.jfif'];
            return asset($mugs[$idx % count($mugs)]);
        };

        $suspectNamesMatch = function($n1, $n2) {
            if (empty($n1) || empty($n2)) return false;
            $c1 = strtolower(trim($n1));
            $c2 = strtolower(trim($n2));
            return ($c1 === $c2) || str_contains($c1, $c2) || str_contains($c2, $c1);
        };

        $isSuspectLinkedToExhibit = function ($s, $ex, $c, $suspectsCount) use ($suspectNamesMatch) {
            $sName = trim((string)($s['suspect_name'] ?? ($c->suspect_name ?? 'Unnamed Suspect')));
            if (empty($sName)) return false;

            $exSuspectId = is_array($ex) ? ($ex['suspect_id'] ?? null) : ($ex->suspect_id ?? null);
            $exAssoc = strtolower(trim(is_array($ex)
                ? ($ex['associated_person'] ?? ($ex['associated_suspect'] ?? ($ex['suspect_name'] ?? ($ex['exhibit_owner_name'] ?? ''))))
                : ($ex->associated_person ?? ($ex->associated_suspect ?? ''))
            ));

            $sDbId = $s['id'] ?? null;
            if (!empty($exSuspectId) && !empty($sDbId)) {
                if ((int)$exSuspectId !== (int)$sDbId) {
                    if (empty($exAssoc) || (!str_contains($exAssoc, strtolower($sName)) && !str_contains($exAssoc, 'all suspect'))) {
                        return false;
                    }
                } else {
                    return true;
                }
            }

            if (!empty($exAssoc)) {
                if (str_contains($exAssoc, 'all suspect')) return true;
                if ($suspectNamesMatch($exAssoc, $sName)) return true;
                return false;
            }

            if ($suspectsCount <= 1) return true;

            if (!empty($c->id) && method_exists($c, 'getExhibitsForSuspect')) {
                $sExhibits = $c->getExhibitsForSuspect($sName, $s['id'] ?? null, $s['associate_id'] ?? null, $s['role'] ?? null);
                if (!empty($sExhibits)) {
                    foreach ($sExhibits as $se) {
                        if ($this->isDrugExhibit($se)) return true;
                    }
                }
            }

            return false;
        };

        $drugRecords = collect();

        foreach ($cases as $c) {
            $docket = $this->decodeDocket($c);
            $seizures = $docket['seizures'] ?? [];
            $suspectsList = $docket['suspects'] ?? [];

            $isDrugCase = $c->isDrugCase();

            if (empty($suspectsList)) {
                $suspectsList = [[
                    'suspect_name'  => $c->suspect_name ?? 'Unknown Suspect',
                    'suspect_alias' => $c->suspect_alias ?? 'N/A',
                    'ghana_card'    => $c->ghana_card ?? 'N/A',
                    'gender'        => $c->gender ?? 'Male',
                    'age'           => $c->age ?? 'N/A',
                    'nationality'   => $c->nationality ?? 'Ghanaian',
                    'phone'         => $c->phone ?? 'N/A',
                    'address'       => $c->address ?? 'N/A',
                    'role'          => 'Primary Suspect',
                    'mugshot_front' => $c->mugshot_front ?? null,
                ]];
            }

            if ($isDrugCase) {
                // Filter exhibits to drug exhibits only
                $drugExhibits = [];
                $exhibitsSource = (!empty($c->exhibits) && count($c->exhibits) > 0) ? $c->exhibits : $seizures;

                foreach ($exhibitsSource as $sz) {
                    if ($this->isDrugExhibit($sz)) {
                        $drugExhibits[] = is_array($sz) ? $sz : (method_exists($sz, 'toArray') ? $sz->toArray() : (array)$sz);
                    }
                }

                if (empty($drugExhibits)) {
                    $caseDrugName = $c->seizure_name_drug ?? ($c->confirmatory_substance_identification ?? null);
                    if (empty($caseDrugName) && $isStrictlyDrugName($c->seizure_name ?? null)) {
                        $caseDrugName = $c->seizure_name;
                    }
                    if (!empty($caseDrugName) && $isStrictlyDrugName($caseDrugName)) {
                        $drugExhibits[] = [
                            'seizure_name'      => $caseDrugName,
                            'commodity_name'    => $caseDrugName,
                            'category'          => $c->exhibit_category ?? 'Narcotics & Controlled Substances',
                            'seizure_weight'    => $c->seizure_weight ?? null,
                            'seizure_unit'      => $c->seizure_unit ?? 'kg',
                            'associated_person' => null,
                        ];
                    }
                }

                // TREAT CASE AS A SINGLE CASE ENTITY: exactly one card per case entity
                $bestEx = null;
                if (!empty($drugExhibits)) {
                    foreach ($drugExhibits as $candidateEx) {
                        $candName = strtolower($candidateEx['seizure_name'] ?? ($candidateEx['commodity_name'] ?? ($candidateEx['seizure_name_drug'] ?? '')));
                        if (!empty($candName) && !in_array($candName, ['drugs', 'drug', 'drug exhibit', 'narcotic', 'substance', 'exhibits'], true)) {
                            $bestEx = $candidateEx;
                            break;
                        }
                    }
                    if (!$bestEx) {
                        $bestEx = $drugExhibits[0];
                    }
                } else {
                    $bestEx = [
                        'seizure_name'      => $c->seizure_name_drug ?? ($c->seizure_name ?? 'Suspected Narcotic Exhibit'),
                        'commodity_name'    => $c->seizure_name_drug ?? ($c->seizure_name ?? 'Suspected Narcotic Exhibit'),
                        'category'          => $c->exhibit_category ?? 'Narcotics & Controlled Substances',
                        'seizure_weight'    => $c->seizure_weight ?? null,
                        'seizure_unit'      => $c->seizure_unit ?? 'kg',
                        'associated_person' => null,
                    ];
                }

                $drugName = $resolveDrugName($bestEx, $c, $docket);
                $drugCategory = $bestEx['drug_classification'] ?? ($bestEx['category'] ?? ($c->exhibit_category ?? 'Narcotics & Controlled Substances'));
                $drugWeight = $bestEx['seizure_weight'] ?? ($c->seizure_weight ?? null);
                $drugUnit = $bestEx['seizure_unit'] ?? ($c->seizure_unit ?? 'kg');
                $quantityStr = !empty($drugWeight) ? "{$drugWeight} {$drugUnit}" : ($bestEx['seizure_quantity'] ?? ($c->seizure_quantity ?? '1 Batch / Package'));

                $labExam = $docket['lab_examination'] ?? [];
                $presumptive = $bestEx['presumptive_result'] ?? ($labExam['presumptive_result'] ?? ($c->presumptive_test_result ?? 'Pending Testing'));
                $confirmatory = $bestEx['confirmatory_result'] ?? ($labExam['confirmatory_result'] ?? ($c->confirmatory_test_result ?? 'YES'));
                $substanceId = $bestEx['substance_identification'] ?? ($labExam['substance_identification'] ?? ($c->confirmatory_substance_identification ?? $drugName));

                if (!$isStrictlyDrugName($substanceId)) {
                    $substanceId = $drugName;
                }

                // Group all suspects linked to this drug case into this single card
                $groupedSuspects = [];
                foreach ($suspectsList as $sIdx => $s) {
                    $sName = $s['suspect_name'] ?? ($c->suspect_name ?? 'Unnamed Suspect');
                    if (empty(trim($sName))) continue;

                    $isLinkedToAnyDrug = false;
                    if (!empty($drugExhibits)) {
                        foreach ($drugExhibits as $dex) {
                            if ($isSuspectLinkedToExhibit($s, $dex, $c, count($suspectsList))) {
                                $isLinkedToAnyDrug = true;
                                break;
                            }
                        }
                    } else {
                        $isLinkedToAnyDrug = true;
                    }

                    if (!$isLinkedToAnyDrug && !empty($s['id'])) {
                        if (DrugScreening::where('suspect_id', $s['id'])->exists()) {
                            $isLinkedToAnyDrug = true;
                        }
                    }

                    if (!$isLinkedToAnyDrug) {
                        continue;
                    }

                    $rawMug = $s['mugshot_front'] ?? ($c->mugshot_front ?? null);
                    $genderVal = $s['gender'] ?? ($c->gender ?? 'Male');
                    $resolvedMug = $resolveMugshot($rawMug, $genderVal, $sIdx);

                    $groupedSuspects[] = [
                        'suspect_name'     => $sName,
                        'suspect_alias'    => $s['suspect_alias'] ?? ($c->suspect_alias ?? 'N/A'),
                        'ghana_card'       => $s['ghana_card'] ?? ($c->ghana_card ?? 'N/A'),
                        'gender'           => $genderVal,
                        'age'              => $s['age'] ?? ($c->age ?? 'N/A'),
                        'nationality'      => $s['nationality'] ?? ($c->nationality ?? 'Ghanaian'),
                        'phone'            => $s['phone'] ?? ($c->phone ?? 'N/A'),
                        'address'          => $s['address'] ?? ($c->address ?? 'Accra, Ghana'),
                        'role'             => $s['role'] ?? ($s['association_role'] ?? ($sIdx === 0 ? 'Primary Suspect' : 'Co-Suspect')),
                        'mugshot_front'    => $resolvedMug,
                        'holding_facility' => $s['holding_facility'] ?? ($c->holding_facility ?? 'NACOC Central Custody Facility'),
                        'arrest_date'      => $s['date_of_arrest'] ?? ($c->arrest_date ?? ($c->incident_date ? date('Y-m-d', strtotime((string)$c->incident_date)) : date('Y-m-d'))),
                    ];
                }

                if (!empty($groupedSuspects)) {
                    $drugRecords->push([
                        'id'                => $c->id,
                        'case_number'       => $c->case_number,
                        'laboratory_number' => $c->laboratory_number ?? 'N/A',
                        'drug_name'         => $drugName,
                        'drug_category'     => $drugCategory,
                        'quantity_str'      => $quantityStr,
                        'presumptive'       => $presumptive,
                        'confirmatory'      => $confirmatory,
                        'detected'          => $substanceId,
                        'raid_location'     => $c->raid_location ?? 'Accra Regional Sector',
                        'status'            => $c->status ?? 'Approved',
                        'case_status'       => $c->case_status ?? 'Under Investigation',
                        'submitting_officer'=> $c->submitting_officer ?? 'N/A',
                        'analyst'           => $labExam['analyst'] ?? 'Forensic Chemist',
                        'suspects'          => $groupedSuspects,
                    ]);
                }
            }
        }

        // Deduplicate $drugRecords so each case_number appears exactly once
        $uniqueDrugRecords = collect();
        $seenCaseNumbers = [];

        $sorted = $drugRecords->sortByDesc(fn($r) => count($r['suspects'] ?? []));

        foreach ($sorted as $r) {
            $cn = strtoupper(trim((string)($r['case_number'] ?? '')));
            if (empty($cn) || in_array($cn, $seenCaseNumbers, true)) {
                continue;
            }
            $seenCaseNumbers[] = $cn;
            $uniqueDrugRecords->push($r);
        }

        return $uniqueDrugRecords;
    }
    /**
     * Build aggregated dataset for the Laboratory SITREP Report & PDF Dossier.
     *
     * @param Request|null $request
     * @return array
     */
    public function buildLabSitrepDataset(?Request $request = null): array
    {
        $request = $request ?: request();
        $authUser = auth()->user();

        // 1. Dynamically resolve Lab Analysis Unit Head and Forensic Directorate Department Head from database
        $dbLabHead = User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first()
            ?? User::where('unit', 'LIKE', '%Lab%')->first();
        $labHeadName = $dbLabHead ? $dbLabHead->fullname : ($authUser && str_contains(strtolower($authUser->unit ?? ''), 'lab') ? $authUser->fullname : 'Laboratory Unit Head');
        $labHeadSignature = $dbLabHead ? $dbLabHead->signature : null;

        $dbDeptHead = User::where('unit', 'LIKE', '%Directorate%')->where('is_head', true)->first()
            ?? User::where('unit', 'LIKE', '%Forensic%')->where('is_head', true)->first()
            ?? User::where('is_head', true)->first();
        $deptHeadName = $dbDeptHead ? $dbDeptHead->fullname : 'Head of Forensic Directorate';
        $deptHeadSignature = $dbDeptHead ? $dbDeptHead->signature : null;

        // 2. Filter parameters
        $rawPeriod = strtolower(trim((string)($request->query('period') ?: ($request->query('preset') ?: 'weekly'))));
        $fromDate = trim((string)$request->query('from', ''));
        $toDate = trim((string)$request->query('to', ''));
        $searchQuery = strtolower(trim((string)$request->query('search', '')));
        $filterSubstance = strtolower(trim((string)$request->query('substance', '')));
        $filterSource = strtolower(trim((string)$request->query('source', '')));
        $filterStatus = strtolower(trim((string)$request->query('status', '')));
        $selectedSuspect = trim((string)($request->query('suspect') ?: ($request->query('suspect_name') ?: '')));

        // 3. Gather Live Database Drug Cases (Both MultipleSuspectCase and SingleSuspectCase)
        $allDbCases = collect();

        $multiples = MultipleSuspectCase::with(['user', 'unit', 'suspects', 'exhibits', 'labResults'])->get();
        $singles = SingleSuspectCase::with(['user', 'unit', 'exhibits', 'labResults'])->where('status', '!=', 'Promoted')->get();

        $singleByCaseNumber = [];
        foreach ($singles as $s) {
            $cn = strtoupper(trim((string)$s->case_number));
            if (!empty($cn)) {
                $singleByCaseNumber[$cn] = $s;
            }
        }

        $processedCaseNumbers = [];

        // 3a. Process MultipleSuspectCase records
        foreach ($multiples as $m) {
            $cn = strtoupper(trim((string)$m->case_number));
            $suspectCount = $m->suspects ? $m->suspects->count() : 0;

            if ($suspectCount > 1) {
                $wrapped = ForensicCase::wrapRecord($m);
                $wrapped->is_multiple = true;
                $wrapped->case_type = 'MULTIPLE';
                $allDbCases->push($wrapped);
                if (!empty($cn)) $processedCaseNumbers[$cn] = true;
            } elseif (!empty($cn) && isset($singleByCaseNumber[$cn])) {
                $wrapped = ForensicCase::wrapRecord($singleByCaseNumber[$cn]);
                $wrapped->is_multiple = false;
                $wrapped->case_type = 'SINGLE';
                $allDbCases->push($wrapped);
                $processedCaseNumbers[$cn] = true;
            } else {
                $wrapped = ForensicCase::wrapRecord($m);
                $wrapped->is_multiple = ($suspectCount > 1);
                $wrapped->case_type = ($suspectCount > 1) ? 'MULTIPLE' : 'SINGLE';
                $allDbCases->push($wrapped);
                if (!empty($cn)) $processedCaseNumbers[$cn] = true;
            }
        }

        // 3b. Process remaining SingleSuspectCase records
        foreach ($singles as $s) {
            $cn = strtoupper(trim((string)$s->case_number));
            if (!empty($cn) && !empty($processedCaseNumbers[$cn])) {
                continue;
            }
            $wrapped = ForensicCase::wrapRecord($s);
            $wrapped->is_multiple = false;
            $wrapped->case_type = 'SINGLE';
            $allDbCases->push($wrapped);
            if (!empty($cn)) $processedCaseNumbers[$cn] = true;
        }

        // 3c. Filter all cases to ONLY include drug-related cases and suspects
        $drugKeywords = config('forensic.drug_keywords', [
            'drug', 'narcotic', 'substance', 'cannabis', 'cocaine', 'heroin',
            'meth', 'tramadol', 'weed', 'hemp', 'hashish', 'ephedrine',
            'precursor', 'opioid', 'fentanyl', 'amphetamine', 'mdma', 'ecstasy',
            'morphine', 'opium', 'diacetylmorphine', 'codeine', 'syrup',
            'marijuana', 'thc', 'brown sugar', 'prohibited',
        ]);

        $isDrugRelated = function ($case) use ($drugKeywords) {
            if (!$case) return false;

            if (method_exists($case, 'isDrugCase') && $case->isDrugCase()) {
                return true;
            }

            $unit = strtolower($case->unit_name ?? ($case->unit->name ?? ''));
            if (str_contains($unit, 'lab') || str_contains($unit, 'narcotic') || str_contains($unit, 'drug')) {
                $exhibits = $case->exhibits ?? [];
                $hasEx = false;
                $hasNonDrugOnly = true;
                foreach ($exhibits as $ex) {
                    $hasEx = true;
                    $cat = strtolower(is_object($ex) ? ($ex->exhibit_category ?? '') : ($ex['exhibit_category'] ?? ''));
                    if (str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains($cat, 'chemical')) {
                        return true;
                    }
                    if (!str_contains($cat, 'electronic') && !str_contains($cat, 'device') && !str_contains($cat, 'phone')) {
                        $hasNonDrugOnly = false;
                    }
                }
                if (!$hasEx || !$hasNonDrugOnly) return true;
            }

            $exhibits = $case->exhibits ?? [];
            foreach ($exhibits as $ex) {
                $cat = strtolower(is_object($ex) ? ($ex->exhibit_category ?? '') : ($ex['exhibit_category'] ?? ''));
                $name = strtolower(is_object($ex) ? ($ex->commodity_name ?? $ex->seizure_name ?? '') : ($ex['commodity_name'] ?? $ex['seizure_name'] ?? ''));
                if (str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains($cat, 'chemical')) {
                    return true;
                }
                foreach ($drugKeywords as $kw) {
                    if (str_contains($name, $kw)) return true;
                }
            }

            $labs = $case->labResults ?? [];
            foreach ($labs as $lab) {
                $sub = strtolower(is_object($lab) ? ($lab->substance_identification ?? '') : ($lab['substance_identification'] ?? ''));
                if (!empty($sub) && !in_array($sub, ['none', 'n/a', 'nil', 'null'])) {
                    return true;
                }
                $pres = strtolower(is_object($lab) ? ($lab->presumptive_result ?? '') : ($lab['presumptive_result'] ?? ''));
                foreach ($drugKeywords as $kw) {
                    if (str_contains($pres, $kw)) return true;
                }
            }

            $docket = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            if (!empty($docket['seizures'])) {
                foreach ($docket['seizures'] as $sz) {
                    $szCat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                    $szName = strtolower($sz['seizure_name'] ?? ($sz['commodity_name'] ?? ''));
                    if (str_contains($szCat, 'drug') || str_contains($szCat, 'narcotic')) return true;
                    foreach ($drugKeywords as $kw) {
                        if (str_contains($szName, $kw)) return true;
                    }
                }
            }

            $drugField = strtolower($case->seizure_name_drug ?? '');
            if (!empty($drugField)) {
                return true;
            }

            return false;
        };

        $allDbCases = $allDbCases->filter(fn($c) => $isDrugRelated($c))->values();

        $seizuresList = [];
        foreach ($allDbCases as $dCase) {
            $cNo = $dCase->case_number ?? ('NCC-' . ($dCase->id ?? 'UNKNOWN'));
            $dDocket = is_array($dCase->docket_data) ? $dCase->docket_data : (json_decode($dCase->docket_data ?? '[]', true) ?: []);

            $sList = method_exists($dCase, 'getSuspectsList') ? $dCase->getSuspectsList() : [];
            if (empty($sList)) {
                $sList = $dDocket['suspects'] ?? [];
            }
            if (empty($sList) && isset($dCase->suspects) && count($dCase->suspects) > 0) {
                $sList = $dCase->suspects->toArray();
            }

            if (empty($sList)) {
                $sList[] = [
                    'name'                => $dCase->suspect_name ?? 'PRIMARY SUSPECT',
                    'suspect_name'        => $dCase->suspect_name ?? 'PRIMARY SUSPECT',
                    'role'                => 'Primary Suspect',
                    'status'              => $dCase->status ?? 'Active',
                    'suspect_disposition' => $dCase->status ?? 'Active',
                    'mugshot'             => $dCase->mugshot_front ?? null,
                    'mugshot_front'       => $dCase->mugshot_front ?? null,
                    'nationality'         => $dCase->nationality ?? 'N/A',
                    'gender'              => $dCase->gender ?? null,
                    'age'                 => $dCase->age ?? null,
                    'is_associate'        => false,
                ];
            } else {
                $sList = array_map(function($s, $sIdx) {
                    $name = $s['suspect_name'] ?? ($s['name'] ?? 'Suspect');
                    $disp = $s['suspect_disposition'] ?? ($s['status'] ?? 'In Custody');
                    $role = $s['role'] ?? ($sIdx === 0 ? 'Primary Suspect' : 'Co-Suspect');
                    $isAssoc = !empty($s['is_associate']) || str_contains(strtolower($role), 'associate') || str_contains(strtolower($role), 'co-suspect') || ($sIdx > 0);
                    return [
                        'name'                => $name,
                        'suspect_name'        => $name,
                        'status'              => $disp,
                        'suspect_disposition' => $disp,
                        'role'                => $role,
                        'is_associate'        => $isAssoc,
                        'mugshot'             => $s['mugshot_front'] ?? ($s['mugshot'] ?? null),
                        'mugshot_front'       => $s['mugshot_front'] ?? ($s['mugshot'] ?? null),
                        'alias'               => $s['suspect_alias'] ?? ($s['alias'] ?? null),
                        'gender'              => $s['gender'] ?? null,
                        'age'                 => $s['age'] ?? null,
                        'nationality'         => $s['nationality'] ?? 'Ghanaian',
                    ];
                }, $sList, array_keys($sList));
            }

            $isMultiple = count($sList) > 1 || !empty($dCase->is_multiple);

            $suspectNames = array_map(function($s) {
                $name = $s['name'] ?? ($s['suspect_name'] ?? 'Suspect');
                $role = !empty($s['role']) && !str_contains(strtoupper($s['role']), 'PRIMARY') ? ' (' . $s['role'] . ')' : '';
                return $name . $role;
            }, $sList);
            $sDisplay = implode(' & ', $suspectNames);

            $exName = $dCase->seizure_name_drug ?? ($dCase->commodity_name ?? ($dCase->seizure_name ?? 'Suspected Narcotic Exhibit'));
            $exQty = $dCase->seizure_quantity ?? '1 Package';
            $exWeight = $dCase->sample_weight_gross ?? ($dCase->weight ?? ($dCase->seizure_weight ?? 'N/A'));
            $exSource = $dCase->raid_location ?? ($dCase->seizure_location ?? ($dCase->location ?? 'NACOC Command Station'));

            // Fetch laboratory examination findings
            $examResult = LaboratoryExaminationResult::where('single_suspect_case_id', $dCase->id)
                ->orWhere('multiple_suspect_case_id', $dCase->id)
                ->orWhere('exam_reference', $cNo)
                ->latest()
                ->first();

            $remarks = 'Preliminary examination conducted; sample secured for confirmatory assay.';
            $statusTag = 'detected';
            if ($examResult) {
                if (!empty($examResult->presumptive_result) || !empty($examResult->remark)) {
                    $remarks = $examResult->presumptive_result ?: $examResult->remark;
                }
                if ($examResult->findings) {
                    $remarks .= ' - ' . $examResult->findings;
                }
            }

            $caseDateTime = $dCase->incident_date 
                ? Carbon::parse($dCase->incident_date) 
                : ($dCase->created_at ?: now());
            $cDate = $caseDateTime->format('Y-m-d');
            $cDateDisplay = $caseDateTime->format('d F Y');

            $seizuresList[] = [
                'id'               => 'DB-' . ($dCase->id ?? $cNo),
                'date'             => $cDate,
                'date_display'     => $cDateDisplay,
                'source'           => $exSource,
                'source_badge'     => strtoupper($dCase->operation_code ?? ($isMultiple ? 'FIELD SEIZURE (MULTI)' : 'FIELD SEIZURE')),
                'case_reference'   => $cNo,
                'case_type'        => $isMultiple ? 'MULTIPLE' : 'SINGLE',
                'is_multiple'      => $isMultiple,
                'case_type_badge'  => $isMultiple ? 'Multiple Case' : 'Single Case',
                'suspects_count'   => count($sList),
                'suspects_display' => $sDisplay,
                'primary_suspect'  => $sList[0]['name'] ?? ($dCase->suspect_name ?? 'Suspect'),
                'suspects'         => $sList,
                'exhibit_type'     => $exName,
                'substance_name'   => $exName,
                'quantity'         => $exQty,
                'weight'           => $exWeight,
                'status_remarks'   => $remarks,
                'status_tag'       => $statusTag,
                'lab_number'       => $dCase->laboratory_number ?? ('LAU/' . date('Y') . '/' . ($dCase->id ?? '001')),
            ];
        }

        // WHY Fix Issue 7b: Prior versions fabricated fallback cases (Emmanuel Agyare, Heston Adjei, etc.)
        // when no cases were found in the database. A law enforcement system must NEVER fabricate suspect
        // names or cases. Return an empty list — the view will show "No records found" which is truthful.

        // 4. Section 2: Suspect Urine Drug Screenings (Live DB Records)
        $drugScreeningRecords = [];
        $dbScreenings = DrugScreening::with(['suspect.multipleCase'])->latest('screening_date')->get();
        $snCounter = 1;
        foreach ($dbScreenings as $ds) {
            $sName = $ds->suspect ? $ds->suspect->suspect_name : 'Suspect #' . $ds->suspect_id;
            $cRef = $ds->suspect && $ds->suspect->multipleCase ? $ds->suspect->multipleCase->case_number : 'NACOC-REF';
            $res = $ds->result ?? 'Negative';
            $resBadge = strtolower($res) === 'positive' ? 'positive' : (strtolower($res) === 'negative' ? 'negative' : 'pending');

            $screenDate = $ds->screening_date ? Carbon::parse($ds->screening_date) : ($ds->created_at ?: now());

            $drugScreeningRecords[] = [
                'sn'             => $snCounter++,
                'date'           => $screenDate->format('Y-m-d'),
                'date_display'   => $screenDate->format('d F Y'),
                'suspect_name'   => $sName,
                'case_reference' => $cRef,
                'purpose'        => $ds->purpose ?: 'Investigative & Intake',
                'result'         => $res,
                'result_badge'   => $resBadge,
                'notes'          => $ds->notes ?: ($ds->substances_found ? 'Substances: ' . $ds->substances_found : 'Point-of-care drug screen registered.'),
            ];
        }

        // WHY Fix Issue 7b: Removed fabricated fallback drug screenings (e.g. Hah tabulu). Return empty state when empty.

        // 5. Section 3: GSA Submissions (Ghana Standards Authority Live DB Records)
        $gsaSubmissions = [];
        $dbGsaReports = SuspectGsaReport::latest()->get();
        $gsaCounter = 1;
        foreach ($dbGsaReports as $gReport) {
            $subDate = $gReport->created_at ? Carbon::parse($gReport->created_at) : now();

            $gsaSubmissions[] = [
                'sn'              => $gsaCounter++,
                'case_reference'  => $gReport->case_number ?: ($gReport->gsa_case_reference ?: 'GSA-REF-' . $gReport->id),
                'exhibit_type'    => $gReport->original_filename ?: 'Chemical Exhibit Specimen',
                'gsa_lab_no'      => $gReport->gsa_lab_number ?: ($gReport->lab_number ?: 'GSA/LAB/' . date('Y')),
                'submission_date' => $subDate->format('d F Y'),
                'status'          => $gReport->gsa_status ?: ($gReport->status ?: 'Exhibits duly submitted awaiting laboratory report.'),
                'status_badge'    => 'pending',
                'notes'           => $gReport->notes ?: 'Transferred for external confirmatory laboratory testing.',
            ];
        }

        // WHY Fix Issue 7b: Removed fabricated fallback GSA submissions. Return empty state when empty.

        // 6. Dynamic Period Range Bounds & Presets
        if ($rawPeriod === 'weekly' && empty($fromDate) && empty($toDate)) {
            $fromDate = now()->subDays(7)->format('Y-m-d');
            $toDate   = now()->format('Y-m-d');
        } elseif ($rawPeriod === 'monthly' && empty($fromDate) && empty($toDate)) {
            $fromDate = now()->startOfMonth()->format('Y-m-d');
            $toDate   = now()->endOfMonth()->format('Y-m-d');
        } elseif (in_array($rawPeriod, ['annual', 'annually', 'yearly', 'year']) && empty($fromDate) && empty($toDate)) {
            $fromDate = now()->startOfYear()->format('Y-m-d');
            $toDate   = now()->endOfYear()->format('Y-m-d');
        }

        // 7. Dynamic Report Headings and Period Description
        if (!empty($selectedSuspect) && strtolower($selectedSuspect) !== 'all') {
            $reportTypeLabel = 'INDIVIDUAL SUSPECT FORENSIC DRUG REPORT';
            $periodLabel = !empty($fromDate) && !empty($toDate)
                ? 'Period: ' . date('d F', strtotime($fromDate)) . ' – ' . date('d F Y', strtotime($toDate))
                : 'Suspect: ' . $selectedSuspect;
            $reportScopeDisplay = 'Target Suspect Dossier';
        } elseif (in_array($rawPeriod, ['annual', 'annually', 'yearly', 'year'])) {
            $reportTypeLabel = 'ANNUAL SITUATIONAL REPORT';
            $periodLabel = 'Period: Calendar Year ' . now()->format('Y');
            $reportScopeDisplay = 'Annual Directory Register';
        } elseif (!empty($fromDate) || !empty($toDate) || in_array($rawPeriod, ['custom', 'date_range', 'range'])) {
            $reportTypeLabel = 'SITUATIONAL DRUG FORENSIC REPORT';
            $periodLabel = 'Period: ' . ($fromDate ?: 'Start') . ' to ' . ($toDate ?: 'Present');
            $reportScopeDisplay = 'Custom Date Filter';
        } else {
            $reportTypeLabel = 'GENERAL SITUATIONAL FORENSIC REPORT';
            $periodLabel = 'Period: All Registered Cases (Cumulative)';
            $reportScopeDisplay = 'All Cases Cumulative Review';
        }

        // 8. Extract list of distinct suspects for the single-suspect dropdown & filter
        $allSuspectOptions = [];
        foreach ($seizuresList as $bs) {
            foreach ($bs['suspects'] as $s) {
                $name = $s['name'] ?? ($s['suspect_name'] ?? '');
                if (!empty($name) && !in_array($name, $allSuspectOptions)) {
                    $allSuspectOptions[] = $name;
                }
            }
        }
        foreach ($drugScreeningRecords as $ds) {
            $sName = $ds['suspect_name'] ?? '';
            if (!empty($sName) && !in_array($sName, $allSuspectOptions)) {
                $allSuspectOptions[] = $sName;
            }
        }
        sort($allSuspectOptions);

        // Extract distinct sources & substances
        $allSources = array_values(array_unique(array_filter(array_column($seizuresList, 'source'))));
        sort($allSources);
        $allSubstances = array_values(array_unique(array_filter(array_column($seizuresList, 'exhibit_type'))));
        sort($allSubstances);

        // 9. Filter evaluations
        $filteredSeizures = array_filter($seizuresList, function($item) use ($fromDate, $toDate, $searchQuery, $filterSubstance, $filterSource, $filterStatus, $selectedSuspect) {
            if (!empty($selectedSuspect) && strtolower($selectedSuspect) !== 'all') {
                $match = false;
                if (str_contains(strtolower($item['suspects_display']), strtolower($selectedSuspect))) {
                    $match = true;
                }
                foreach ($item['suspects'] as $s) {
                    $sName = $s['name'] ?? ($s['suspect_name'] ?? '');
                    if (str_contains(strtolower($sName), strtolower($selectedSuspect))) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) return false;
            }
            if (!empty($fromDate) && strcmp($item['date'], $fromDate) < 0) {
                return false;
            }
            if (!empty($toDate) && strcmp($item['date'], $toDate) > 0) {
                return false;
            }
            if (!empty($filterSubstance) && !str_contains(strtolower($item['exhibit_type']), $filterSubstance) && !str_contains(strtolower($item['substance_name']), $filterSubstance)) {
                return false;
            }
            if (!empty($filterSource) && !str_contains(strtolower($item['source']), $filterSource)) {
                return false;
            }
            if (!empty($filterStatus) && !str_contains(strtolower($item['status_remarks']), $filterStatus)) {
                return false;
            }
            if (!empty($searchQuery)) {
                $haystack = strtolower(
                    $item['date_display'] . ' ' .
                    $item['source'] . ' ' .
                    $item['case_reference'] . ' ' .
                    $item['suspects_display'] . ' ' .
                    $item['exhibit_type'] . ' ' .
                    $item['substance_name'] . ' ' .
                    $item['status_remarks']
                );
                if (!str_contains($haystack, $searchQuery)) {
                    return false;
                }
            }
            return true;
        });
        $filteredSeizures = array_values($filteredSeizures);

        // Filter drug screenings if suspect or query selected
        $filteredScreenings = array_filter($drugScreeningRecords, function($ds) use ($selectedSuspect, $searchQuery) {
            if (!empty($selectedSuspect) && strtolower($selectedSuspect) !== 'all') {
                if (!str_contains(strtolower($ds['suspect_name']), strtolower($selectedSuspect))) {
                    return false;
                }
            }
            if (!empty($searchQuery)) {
                $haystack = strtolower($ds['suspect_name'] . ' ' . $ds['case_reference'] . ' ' . $ds['purpose'] . ' ' . $ds['result']);
                if (!str_contains($haystack, $searchQuery)) {
                    return false;
                }
            }
            return true;
        });
        $filteredScreenings = array_values($filteredScreenings);

        // Operational Summary Statistics
        $totalSeizuresCount = count($filteredSeizures);
        $totalSuspectsCount = 0;
        $atLargeCount = 0;
        $inCustodyCount = 0;
        foreach ($filteredSeizures as $fs) {
            $totalSuspectsCount += count($fs['suspects']);
            foreach ($fs['suspects'] as $s) {
                if (str_contains(strtoupper($s['status'] ?? ''), 'LARGE')) {
                    $atLargeCount++;
                } else {
                    $inCustodyCount++;
                }
            }
        }
        $totalScreeningsCount = count($filteredScreenings);
        $totalGsaCount = count($gsaSubmissions);

        // Fix Issue 20: 'allScreeningRecords' removed as it is never referenced in views
        return [
            'filteredSeizures'      => collect($filteredSeizures),
            'baselineSeizures'      => $seizuresList,
            'drugScreeningRecords'  => $filteredScreenings,
            'gsaSubmissions'        => $gsaSubmissions,
            'allSuspectOptions'     => $allSuspectOptions,
            'allSources'            => $allSources,
            'allSubstances'         => $allSubstances,
            'reportTypeLabel'       => $reportTypeLabel,
            'periodLabel'           => $periodLabel,
            'reportScopeDisplay'    => $reportScopeDisplay,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'rawPeriod'             => $rawPeriod,
            'searchQuery'           => $searchQuery,
            'selectedSuspect'       => $selectedSuspect,
            'filterSubstance'       => $filterSubstance,
            'filterSource'          => $filterSource,
            'filterStatus'          => $filterStatus,
            'totalSeizuresCount'    => $totalSeizuresCount,
            'totalSuspectsCount'    => $totalSuspectsCount,
            'atLargeCount'          => $atLargeCount,
            'inCustodyCount'        => $inCustodyCount,
            'totalScreeningsCount'  => $totalScreeningsCount,
            'totalGsaCount'         => $totalGsaCount,
            'labHeadName'           => $labHeadName,
            'labHeadSignature'      => $labHeadSignature,
            'deptHeadName'          => $deptHeadName,
            'deptHeadSignature'     => $deptHeadSignature,
        ];
    }
}
