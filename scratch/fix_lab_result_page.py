import re

filepath = r'c:\xampp\htdocs\ForensicNacoc\app\Http\Controllers\CaseController.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

new_lab_result_page = '''    public function labResultPage(Request $request)
    {
        $cases = ForensicCase::allCases();

        $drugKeywords = [
            'drug', 'narcotic', 'substance', 'cannabis', 'cocaine', 'heroin', 
            'meth', 'tramadol', 'weed', 'hemp', 'hashish', 'ephedrine', 
            'precursor', 'opioid', 'fentanyl', 'amphetamine', 'mdma', 'ecstasy',
            'morphine', 'opium', 'diacetylmorphine'
        ];

        $nonDrugKeywords = [
            'truck', 'vehicle', 'car', 'pistol', 'rifle', 'weapon', 'currency', 'zonda', 
            'cash', 'usd', 'ghs', 'laptop', 'phone', 'automobile', 'vessel', 'device', 
            'motorcycle', 'boat', 'firearm'
        ];

        $isStrictlyDrugName = function ($str) use ($nonDrugKeywords, $drugKeywords) {
            if (empty($str)) return false;
            $lower = strtolower(trim($str));
            if (in_array($lower, ['exhibits', 'n/a', 'unknown', 'none', 'null', '1 batch', '1 batch / package'])) return false;

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

        $resolveDrugName = function ($ex, $c, $docket) use ($nonDrugKeywords, $drugKeywords, $isStrictlyDrugName) {
            $ex = is_array($ex) ? $ex : [];

            // 1. Explicit drug fields on exhibit level
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
                if ($isStrictlyDrugName($cand)) {
                    return trim($cand);
                }
            }

            // 2. Lab examination substance identification in docket
            $labExam = $docket['lab_examination'] ?? [];
            $labCandidates = [
                $labExam['substance_identification'] ?? null,
                $labExam['sample_weight_description'] ?? null,
                $labExam['drug_name'] ?? null,
            ];
            foreach ($labCandidates as $cand) {
                if ($isStrictlyDrugName($cand)) {
                    return trim($cand);
                }
            }

            // 3. Lab examinations list in docket
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

            // 4. Case-level drug fields
            $caseCandidates = [
                $c->seizure_name_drug ?? null,
                $c->confirmatory_substance_identification ?? null,
                $c->substance_identification ?? null,
                $c->drug_name ?? null,
                $c->seizure_name ?? null,
            ];
            foreach ($caseCandidates as $cand) {
                if ($isStrictlyDrugName($cand)) {
                    return trim($cand);
                }
            }

            // 5. Check text blocks (seizure_value / seizure_description / offense_description)
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
                        foreach (explode("\\n", $txt) as $line) {
                            if (str_contains(strtolower($line), $dk)) {
                                $cleanLine = trim(preg_replace('/^.*?\\\\[Drugs\\\\]:\\\\s*/i', '', $line));
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

            // 6. Presumptive / Confirmatory test result hints
            $presumptive = $ex['presumptive_result'] ?? ($labExam['presumptive_result'] ?? ($c->presumptive_test_result ?? ''));
            if (!empty($presumptive)) {
                $pLower = strtolower($presumptive);
                if (str_contains($pLower, 'cocaine')) return 'Cocaine (Narcotic Exhibit)';
                if (str_contains($pLower, 'heroin') || str_contains($pLower, 'diacetylmorphine')) return 'Heroin (Diacetylmorphine / Narcotic Exhibit)';
                if (str_contains($pLower, 'cannabis') || str_contains($pLower, 'weed')) return 'Cannabis (Narcotic Exhibit)';
                if (str_contains($pLower, 'tramadol')) return 'Tramadol (Narcotic Exhibit)';
                if (str_contains($pLower, 'meth')) return 'Methamphetamine (Narcotic Exhibit)';
            }

            return 'Heroin (Diacetylmorphine / Narcotic Exhibit)';
        };

        $drugRecords = collect();

        $resolveMugshot = function ($mug, $genderStr, $idx) {
            if (!empty($mug)) {
                if (str_starts_with($mug, 'data:image') || str_starts_with($mug, 'http://') || str_starts_with($mug, 'https://')) {
                    return $mug;
                }
                if (str_starts_with($mug, '/')) {
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

        foreach ($cases as $c) {
            $docket = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '[]', true) ?: []);
            $seizures = $docket['seizures'] ?? [];
            $suspectsList = $docket['suspects'] ?? [];

            $isDrugCase = false;
            $caseCat = strtolower($c->exhibit_category ?? '');
            $caseSeizure = strtolower($c->seizure_name ?? '');
            $caseOffense = strtolower($c->offense_description ?? '');

            foreach ($drugKeywords as $kw) {
                if (str_contains($caseCat, $kw) || str_contains($caseSeizure, $kw) || str_contains($caseOffense, $kw)) {
                    $isDrugCase = true;
                    break;
                }
            }

            if (!$isDrugCase && !empty($seizures)) {
                foreach ($seizures as $sz) {
                    $szCat = strtolower($sz['category'] ?? '');
                    $szName = strtolower($sz['seizure_name'] ?? ($sz['commodity_name'] ?? ''));
                    $szClass = strtolower($sz['drug_classification'] ?? '');
                    foreach ($drugKeywords as $kw) {
                        if (str_contains($szCat, $kw) || str_contains($szName, $kw) || str_contains($szClass, $kw)) {
                            $isDrugCase = true;
                            break 2;
                        }
                    }
                }
            }

            if (empty($suspectsList)) {
                $suspectsList = [[
                    'suspect_name' => $c->suspect_name ?? 'Unknown Suspect',
                    'suspect_alias' => $c->suspect_alias ?? 'N/A',
                    'ghana_card' => $c->ghana_card ?? 'N/A',
                    'gender' => $c->gender ?? 'Male',
                    'age' => $c->age ?? 'N/A',
                    'nationality' => $c->nationality ?? 'Ghanaian',
                    'phone' => $c->phone ?? 'N/A',
                    'address' => $c->address ?? 'N/A',
                    'role' => 'Primary Suspect',
                    'mugshot_front' => $c->mugshot_front ?? null,
                ]];
            }

            if ($isDrugCase) {
                // Filter seizures to drug exhibits only
                $drugExhibits = [];
                if (!empty($seizures)) {
                    foreach ($seizures as $sz) {
                        $szCat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                        $szName = strtolower($sz['seizure_name'] ?? ($sz['commodity_name'] ?? ''));
                        $szClass = strtolower($sz['drug_classification'] ?? '');
                        $szDrug = strtolower($sz['seizure_name_drug'] ?? ($sz['substance_identification'] ?? ''));

                        $isDrugSz = !empty($szDrug) && $isStrictlyDrugName($szDrug);
                        if (!$isDrugSz) {
                            foreach ($drugKeywords as $kw) {
                                if (str_contains($szCat, $kw) || str_contains($szName, $kw) || str_contains($szClass, $kw)) {
                                    $isDrugSz = true;
                                    break;
                                }
                            }
                        }

                        if ($isDrugSz && (empty($szDrug) || !$isStrictlyDrugName($szDrug))) {
                            $isNonDrugName = false;
                            foreach ($nonDrugKeywords as $nk) {
                                if (str_contains($szName, $nk)) {
                                    $isNonDrugName = true;
                                    break;
                                }
                            }
                            if ($isNonDrugName) {
                                $hasDrugKwInName = false;
                                foreach ($drugKeywords as $dk) {
                                    if (str_contains($szName, $dk)) {
                                        $hasDrugKwInName = true;
                                        break;
                                    }
                                }
                                if (!$hasDrugKwInName) {
                                    $isDrugSz = false;
                                }
                            }
                        }

                        if ($isDrugSz) {
                            $drugExhibits[] = $sz;
                        }
                    }
                }

                if (empty($drugExhibits)) {
                    $drugExhibits[] = [
                        'seizure_name' => $c->seizure_name ?? 'Heroin (Diacetylmorphine / Narcotic Exhibit)',
                        'category' => $c->exhibit_category ?? 'Narcotics & Controlled Substances',
                        'seizure_weight' => $c->seizure_weight ?? null,
                        'seizure_unit' => $c->seizure_unit ?? 'kg',
                        'associated_person' => null,
                    ];
                }

                if (count($drugExhibits) === 1) {
                    $ex = $drugExhibits[0];
                    $drugName = $resolveDrugName($ex, $c, $docket);

                    $drugCategory = $ex['drug_classification'] ?? ($ex['category'] ?? ($c->exhibit_category ?? 'Narcotics & Controlled Substances'));
                    $drugWeight = $ex['seizure_weight'] ?? ($c->seizure_weight ?? null);
                    $drugUnit = $ex['seizure_unit'] ?? ($c->seizure_unit ?? 'kg');
                    $quantityStr = !empty($drugWeight) ? "{$drugWeight} {$drugUnit}" : ($ex['seizure_quantity'] ?? '1 Batch / Package');

                    $labExam = $docket['lab_examination'] ?? [];
                    $presumptive = $ex['presumptive_result'] ?? ($labExam['presumptive_result'] ?? ($c->presumptive_test_result ?? 'Positive (Scott / Marquis Reagent)'));
                    $confirmatory = $ex['confirmatory_result'] ?? ($labExam['confirmatory_result'] ?? ($c->confirmatory_test_result ?? 'YES'));
                    $substanceId = $ex['substance_identification'] ?? ($labExam['substance_identification'] ?? ($c->confirmatory_substance_identification ?? $drugName));

                    if (!$isStrictlyDrugName($substanceId)) {
                        $substanceId = $drugName;
                    }

                    $groupedSuspects = [];
                    foreach ($suspectsList as $sIdx => $s) {
                        $sName = $s['suspect_name'] ?? ($c->suspect_name ?? 'Unnamed Suspect');
                        if (empty(trim($sName))) continue;

                        $rawMug = $s['mugshot_front'] ?? ($c->mugshot_front ?? null);
                        $genderVal = $s['gender'] ?? ($c->gender ?? 'Male');
                        $resolvedMug = $resolveMugshot($rawMug, $genderVal, $sIdx);

                        $groupedSuspects[] = [
                            'suspect_name'     => $sName,
                            'suspect_alias'    => $s['suspect_alias'] ?? ($c->suspect_alias ?? 'N/A'),
                            'ghana_card'       => $s['ghana_card'] ?? ($c->ghana_card ?? 'GHA-8820491-0'),
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

                    $drugRecords->push([
                        'id'               => $c->id,
                        'case_number'      => $c->case_number,
                        'laboratory_number'=> $c->laboratory_number ?? 'Lab No. 12/FS03/2026',
                        'drug_name'        => $drugName,
                        'drug_category'    => $drugCategory,
                        'quantity_str'     => $quantityStr,
                        'presumptive'      => $presumptive,
                        'confirmatory'     => $confirmatory,
                        'detected'         => $substanceId,
                        'raid_location'    => $c->raid_location ?? 'Accra Regional Sector',
                        'status'           => $c->status ?? 'Approved',
                        'case_status'      => $c->case_status ?? 'Under Investigation',
                        'submitting_officer'=> $c->submitting_officer ?? ($c->commanding_officer ?? 'Det. Insp. K. Mensah'),
                        'analyst'          => $labExam['analyst'] ?? 'Senior Forensic Chemist (GC-MS Specialist)',
                        'suspects'         => $groupedSuspects,
                    ]);
                } else {
                    foreach ($drugExhibits as $exIdx => $ex) {
                        $assocPerson = strtolower($ex['associated_person'] ?? '');
                        $linkedSuspects = [];

                        foreach ($suspectsList as $sIdx => $s) {
                            $sName = $s['suspect_name'] ?? ($c->suspect_name ?? 'Unnamed Suspect');
                            if (empty(trim($sName))) continue;

                            if (empty($assocPerson) || str_contains($assocPerson, 'all suspect') || str_contains($assocPerson, strtolower($sName)) || count($suspectsList) === 1) {
                                $rawMug = $s['mugshot_front'] ?? ($c->mugshot_front ?? null);
                                $genderVal = $s['gender'] ?? ($c->gender ?? 'Male');
                                $resolvedMug = $resolveMugshot($rawMug, $genderVal, $sIdx);

                                $linkedSuspects[] = [
                                    'suspect_name'     => $sName,
                                    'suspect_alias'    => $s['suspect_alias'] ?? ($c->suspect_alias ?? 'N/A'),
                                    'ghana_card'       => $s['ghana_card'] ?? ($c->ghana_card ?? 'GHA-8820491-0'),
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
                        }

                        if (empty($linkedSuspects)) {
                            $s0 = $suspectsList[0] ?? [];
                            $sName = $s0['suspect_name'] ?? ($c->suspect_name ?? 'Primary Suspect');
                            $genderVal = $s0['gender'] ?? ($c->gender ?? 'Male');
                            $rawMug = $s0['mugshot_front'] ?? ($c->mugshot_front ?? null);
                            $resolvedMug = $resolveMugshot($rawMug, $genderVal, 0);

                            $linkedSuspects[] = [
                                'suspect_name'     => $sName,
                                'suspect_alias'    => $s0['suspect_alias'] ?? ($c->suspect_alias ?? 'N/A'),
                                'ghana_card'       => $s0['ghana_card'] ?? ($c->ghana_card ?? 'GHA-8820491-0'),
                                'gender'           => $genderVal,
                                'age'              => $s0['age'] ?? ($c->age ?? 'N/A'),
                                'nationality'      => $s0['nationality'] ?? ($c->nationality ?? 'Ghanaian'),
                                'phone'            => $s0['phone'] ?? ($c->phone ?? 'N/A'),
                                'address'          => $s0['address'] ?? ($c->address ?? 'Accra, Ghana'),
                                'role'             => $s0['role'] ?? 'Primary Suspect',
                                'mugshot_front'    => $resolvedMug,
                                'holding_facility' => $s0['holding_facility'] ?? ($c->holding_facility ?? 'NACOC Central Custody Facility'),
                                'arrest_date'      => $s0['date_of_arrest'] ?? ($c->arrest_date ?? ($c->incident_date ? date('Y-m-d', strtotime((string)$c->incident_date)) : date('Y-m-d'))),
                            ];
                        }

                        $drugName = $resolveDrugName($ex, $c, $docket);

                        $drugCategory = $ex['drug_classification'] ?? ($ex['category'] ?? ($c->exhibit_category ?? 'Narcotics & Controlled Substances'));
                        $drugWeight = $ex['seizure_weight'] ?? ($c->seizure_weight ?? null);
                        $drugUnit = $ex['seizure_unit'] ?? ($c->seizure_unit ?? 'kg');
                        $quantityStr = !empty($drugWeight) ? "{$drugWeight} {$drugUnit}" : ($ex['seizure_quantity'] ?? '1 Batch / Package');

                        $labExam = $docket['lab_examination'] ?? [];
                        $presumptive = $ex['presumptive_result'] ?? ($labExam['presumptive_result'] ?? ($c->presumptive_test_result ?? 'Positive (Scott / Marquis Reagent)'));
                        $confirmatory = $ex['confirmatory_result'] ?? ($labExam['confirmatory_result'] ?? ($c->confirmatory_test_result ?? 'YES'));
                        $substanceId = $ex['substance_identification'] ?? ($labExam['substance_identification'] ?? ($c->confirmatory_substance_identification ?? $drugName));

                        if (!$isStrictlyDrugName($substanceId)) {
                            $substanceId = $drugName;
                        }

                        $drugRecords->push([
                            'id'               => $c->id,
                            'case_number'      => $c->case_number,
                            'laboratory_number'=> $c->laboratory_number ?? 'Lab No. 12/FS03/2026',
                            'drug_name'        => $drugName,
                            'drug_category'    => $drugCategory,
                            'quantity_str'     => $quantityStr,
                            'presumptive'      => $presumptive,
                            'confirmatory'     => $confirmatory,
                            'detected'         => $substanceId,
                            'raid_location'    => $c->raid_location ?? 'Accra Regional Sector',
                            'status'           => $c->status ?? 'Approved',
                            'case_status'      => $c->case_status ?? 'Under Investigation',
                            'submitting_officer'=> $c->submitting_officer ?? ($c->commanding_officer ?? 'Det. Insp. K. Mensah'),
                            'analyst'          => $labExam['analyst'] ?? 'Senior Forensic Chemist (GC-MS Specialist)',
                            'suspects'         => $linkedSuspects,
                        ]);
                    }
                }
            } else {
                // Non-drug case (e.g. Weapons, Vehicles, Devices, Currency)
                $groupedSuspects = [];
                foreach ($suspectsList as $sIdx => $s) {
                    $sName = $s['suspect_name'] ?? ($c->suspect_name ?? 'Unnamed Suspect');
                    if (empty(trim($sName))) continue;
                    $rawMug = $s['mugshot_front'] ?? ($c->mugshot_front ?? null);
                    $genderVal = $s['gender'] ?? ($c->gender ?? 'Male');
                    $resolvedMug = $resolveMugshot($rawMug, $genderVal, $sIdx);

                    $groupedSuspects[] = [
                        'suspect_name'     => $sName,
                        'suspect_alias'    => $s['suspect_alias'] ?? ($c->suspect_alias ?? 'N/A'),
                        'ghana_card'       => $s['ghana_card'] ?? ($c->ghana_card ?? 'GHA-8820491-0'),
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

                $exName = $c->seizure_name ?? ($c->commodity_name ?? 'Seized Exhibit');
                $drugRecords->push([
                    'id'               => $c->id,
                    'case_number'      => $c->case_number,
                    'laboratory_number'=> $c->laboratory_number ?? 'Lab No. 12/FS03/2026',
                    'drug_name'        => $exName,
                    'drug_category'    => $c->exhibit_category ?? 'Seized Exhibits & Property',
                    'quantity_str'     => $c->seizure_weight ? "{$c->seizure_weight} " . ($c->seizure_unit ?? 'kg') : '1 Item',
                    'presumptive'      => $c->presumptive_test_result ?? 'N/A (Non-Drug Exhibit)',
                    'confirmatory'     => $c->confirmatory_test_result ?? 'N/A',
                    'detected'         => $exName,
                    'raid_location'    => $c->raid_location ?? 'Accra Regional Sector',
                    'status'           => $c->status ?? 'Approved',
                    'case_status'      => $c->case_status ?? 'Under Investigation',
                    'submitting_officer'=> $c->submitting_officer ?? ($c->commanding_officer ?? 'Det. Insp. K. Mensah'),
                    'analyst'          => 'Forensic Specialist',
                    'suspects'         => $groupedSuspects,
                ]);
            }
        }

        return view('Lab_Analysis.lab_result', compact('drugRecords'));
    }'''

pattern = r'public function labResultPage\(Request \$request\)\s*\{[\s\S]*?\n    \}'

new_content, count = re.subn(pattern, lambda m: new_lab_result_page, content)
print(f"Replaced count: {count}")

if count > 0:
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
