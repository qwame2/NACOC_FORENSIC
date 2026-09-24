@php
    $isLabAnalysis = true;
@endphp
@php
    $case = $case ?? null;

    $hasDrugExhibitLinkedToSuspect = false;

    if ($case) {
        // Must be a drug case at all
        $isDrug = $case->isDrugCase();

        if ($isDrug) {
            $docket = is_array($case->docket_data)
                ? $case->docket_data
                : (json_decode($case->docket_data ?? '[]', true) ?: []);

            $seizures = $docket['seizures'] ?? [];

            if (is_array($seizures)) {
                foreach ($seizures as $sz) {
                    // Determine if this seizure is a drug exhibit
                    $cat  = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                    $drug = strtolower($sz['seizure_name_drug'] ?? '');
                    $name = strtolower($sz['seizure_name'] ?? '');

                    $isDrugExhibit = !empty($drug)
                        || str_contains($cat, 'drug')
                        || str_contains($cat, 'narcotic')
                        || str_contains($cat, 'substance')
                        || str_contains($cat, 'cannabis')
                        || str_contains($cat, 'cocaine')
                        || str_contains($cat, 'heroin')
                        || str_contains($cat, 'meth')
                        || str_contains($cat, 'tramadol')
                        || str_contains($name, 'cocaine')
                        || str_contains($name, 'heroin')
                        || str_contains($name, 'cannabis')
                        || str_contains($name, 'weed')
                        || str_contains($name, 'meth')
                        || str_contains($name, 'tramadol')
                        || str_contains($name, 'drug')
                        || str_contains($name, 'narcotic');

                    if (!$isDrugExhibit) {
                        continue;
                    }

                    // Must be linked to a suspect
                    $owner = trim($sz['associated_person']
                        ?? ($sz['suspect_name']
                        ?? ($sz['owner_name'] ?? '')));

                    if (!empty($owner)
                        && !in_array(strtolower($owner), [
                            'primary suspect',
                            'associate suspect',
                            'all suspects',
                            'under active investigation',
                        ])) {
                        $hasDrugExhibitLinkedToSuspect = true;
                        break;
                    }
                }
            }
        }
    }
@endphp
@include('cases.create')