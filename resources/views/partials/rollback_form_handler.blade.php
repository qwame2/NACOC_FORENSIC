@php
    if (!isset($rollbackSubmission) && request()->filled('rollback_id')) {
        $rollbackSubmission = \App\Models\StaffCaseSubmission::find(request()->input('rollback_id'));
    }
    $isRollbackMode = !empty($rollbackSubmission);
    $rollbackPayload = $isRollbackMode && is_array($rollbackSubmission->payload) ? $rollbackSubmission->payload : [];
    $rollbackCorrections = $isRollbackMode && is_array($rollbackSubmission->rollback_corrections) ? $rollbackSubmission->rollback_corrections : [];
    $rollbackNotes = $isRollbackMode ? ($rollbackSubmission->review_notes ?? '') : '';
@endphp

@if($isRollbackMode)
<!-- ROLLBACK MODE ACTIVE BANNER & DIRECTIVES -->
<div id="rollbackActiveBanner" class="p-5 rounded-2xl bg-green-100 border-none shadow-sm space-y-3 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-green-200/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-green-700 text-white flex items-center justify-center font-black shadow-xs shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-black text-green-950 text-sm flex items-center gap-2">
                    <span>Active Case Rollback &mdash; Fix Requested Corrections</span>
                    <span class="font-mono-code text-xs px-2 py-0.5 rounded bg-green-200 text-green-950 border-none">
                        {{ $rollbackSubmission->case_number_preview ?: ('SUB-' . str_pad((string)$rollbackSubmission->id, 4, '0', STR_PAD_LEFT)) }}
                    </span>
                </h3>
                <p class="text-xs text-green-800 font-medium">All inputs are pre-filled with your saved docket details and locked as read-only, except the specific section(s) your Unit Head asked you to correct.</p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="px-3 py-1 rounded-full text-xs font-mono-code font-bold bg-green-200 text-green-950 border-none">
                {{ count($rollbackCorrections) }} Section{{ count($rollbackCorrections) === 1 ? '' : 's' }} To Correct
            </span>
        </div>
    </div>

    @if(!empty($rollbackNotes))
    <div class="p-3 bg-white/90 rounded-xl border-none text-xs text-green-950">
        <strong>Unit Head Directives:</strong> {{ $rollbackNotes }}
    </div>
    @endif

    @if(count($rollbackCorrections) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pt-1">
        @foreach($rollbackCorrections as $corr)
            <div class="p-2.5 rounded-xl bg-white border-none shadow-2xs text-xs">
                <div class="font-bold text-green-950 font-mono-code text-[11px] flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                    {{ $corr['field'] ?? 'Field Correction' }}
                </div>
                <div class="text-slate-700 mt-1 font-sans text-xs">{{ $corr['note'] ?? 'Please update requested corrections.' }}</div>
            </div>
        @endforeach
    </div>
    @endif
</div>

<input type="hidden" name="rollback_id" id="rollbackSubmissionIdInput" value="{{ $rollbackSubmission->id }}">
<input type="hidden" name="rollback_mode" value="1">

<script>
(function() {
    window.IS_ROLLBACK_MODE = true;
    window.ROLLBACK_SUBMISSION_ID = {{ $rollbackSubmission->id }};
    window.ROLLBACK_PAYLOAD = @json($rollbackPayload);
    window.ROLLBACK_CORRECTIONS = @json($rollbackCorrections);
    window.ROLLBACK_NOTES = @json($rollbackNotes);
    @php
        $resolvedResubmitUnit = $unitContext['key'] ?? ($unitCtx['key'] ?? '');
        if (!$resolvedResubmitUnit) {
            $p = strtolower(request()->path());
            if (str_contains($p, 'lab_analysis') || str_contains($p, 'lab-analysis')) {
                $resolvedResubmitUnit = 'lab_analysis';
            } elseif (str_contains($p, 'evidence_management') || str_contains($p, 'evidence-management')) {
                $resolvedResubmitUnit = 'evidence_management';
            } elseif (str_contains($p, 'digital_forensics') || str_contains($p, 'digital-forensics')) {
                $resolvedResubmitUnit = 'digital_forensics';
            } elseif (str_contains($p, 'forensic_head') || str_contains($p, 'forensic-head')) {
                $resolvedResubmitUnit = 'forensic_head';
            } else {
                $resolvedResubmitUnit = 'crime_scene_support';
            }
        }
        $computedResubmitUrl = match($resolvedResubmitUnit) {
            'lab_analysis' => url('/Lab_Analysis/staff/case-rollback/' . $rollbackSubmission->id . '/resubmit'),
            'evidence_management' => url('/Evidence_Management/staff/case-rollback/' . $rollbackSubmission->id . '/resubmit'),
            'digital_forensics' => url('/Digital_Forensics/staff/case-rollback/' . $rollbackSubmission->id . '/resubmit'),
            'forensic_head' => url('/Forensic_Head/staff/case-rollback/' . $rollbackSubmission->id . '/resubmit'),
            default => url('/Crime_Scene_Support/staff/case-rollback/' . $rollbackSubmission->id . '/resubmit'),
        };
    @endphp
    window.ROLLBACK_RESUBMIT_URL = "{{ $computedResubmitUrl }}";

    function getCorrectionFieldKey(fieldStr) {
        if (!fieldStr || typeof fieldStr !== 'string') return '';
        const f = fieldStr.toLowerCase().trim();

        // 1. Case Title / Name
        if (f.includes('case name') || f.includes('legal title') || f.includes('case title') || f === 'title') {
            return 'case_title';
        }
        // 2. Suspect Name
        if (f.includes('suspect full name') || f.includes('suspect name') || f === 'full name' || f.includes('suspect_name')) {
            return 'suspect_name';
        }
        // 3. Alias
        if (f.includes('alias')) {
            return 'suspect_alias';
        }
        // 4. Ghana Card / ID
        if (f.includes('ghana card') || f.includes('id card') || f.includes('id number') || f.includes('card')) {
            return 'ghana_card';
        }
        // 5. Gender
        if (f.includes('gender') || f.includes('sex')) {
            return 'gender';
        }
        // 6. DOB & Age
        if (f.includes('date of birth') || f.includes('dob')) {
            return 'dob';
        }
        if (f === 'age') {
            return 'age';
        }
        // 7. Height
        if (f.includes('height')) {
            return 'height';
        }
        // 8. Eye color
        if (f.includes('eye color') || f.includes('eye colour') || f.includes('eye')) {
            return 'eye_color';
        }
        // 9. POB
        if (f.includes('place of birth') || f.includes('pob')) {
            return 'pob';
        }
        // 10. Nationality
        if (f.includes('nationality') || f.includes('citizenship')) {
            return 'nationality';
        }
        // 11. Residential Address
        if (f.includes('residential address') || f.includes('address') || f.includes('residence')) {
            return 'address';
        }
        // 12. Contact number / Phone
        if (f.includes('contact number') || f.includes('phone') || f.includes('tel')) {
            return 'phone';
        }
        // 13. Marital status & Spouse / Partner Details
        if (f.includes('marital') || f.includes('spouse') || f.includes('partner') || f.includes('relationship')) {
            return 'marital';
        }

        // Children / Dependents
        if (f.includes('children') || f.includes('dependent') || f.includes('dependents') || f.includes('child') || f.includes('kid')) {
            return 'children';
        }
        // 14. Location / Raid Scene
        if (f.includes('place of arrest') || f.includes('incident location') || f.includes('raid scene location') || f.includes('raid location') || f.includes('scene location')) {
            return 'location';
        }
        // 15. Arrest Date & Time
        if (f.includes('arrest date') || f.includes('date of arrest')) {
            return 'arrest_date';
        }
        if (f.includes('arrest time') || f.includes('time of arrest')) {
            return 'arrest_time';
        }
        // 16. Holding facility / Custody
        if (f.includes('holding facility') || f.includes('custody') || f.includes('facility')) {
            return 'facility';
        }
        // 17. Offense / Brief Facts
        if (f.includes('offense') || f.includes('offence') || f.includes('brief facts') || f.includes('facts') || f.includes('charge')) {
            return 'offense';
        }
        // 18. Commodity Name / Exhibit Name
        if (f.includes('commodity name') || f.includes('commodity') || f.includes('seizure name') || f.includes('exhibit name')) {
            return 'commodity_name';
        }
        // 19. Exhibit Category
        if (f.includes('exhibit category') || f === 'category') {
            return 'category';
        }
        // 20. Device Model
        if (f.includes('device model') || f === 'model') {
            return 'dev_model';
        }
        // 21. Device Serial
        if (f.includes('serial number') || f.includes('serial no') || f === 'serial') {
            return 'dev_serial';
        }
        // 22. IMEI
        if (f.includes('imei')) {
            return 'dev_imei';
        }
        // 23. MAC
        if (f.includes('mac address') || f.includes('mac')) {
            return 'dev_mac';
        }
        // 24. Storage
        if (f.includes('storage') || f.includes('capacity')) {
            return 'dev_storage';
        }
        // 25. Colour
        if (f.includes('colour') || f.includes('color')) {
            return 'dev_colour';
        }
        // 26. Marks
        if (f.includes('identifying marks') || f.includes('marks')) {
            return 'dev_marks';
        }
        // 27. Concealment
        if (f.includes('concealment') || f.includes('packaging notes')) {
            return 'concealment';
        }
        // 28. Quantity
        if (f.includes('quantity')) {
            return 'quantity';
        }
        // 29. Weight & Unit
        if (f.includes('weight')) {
            return 'weight';
        }
        if (f.includes('unit')) {
            return 'unit';
        }
        // 30. Value
        if (f.includes('value') || f.includes('valuation')) {
            return 'value';
        }
        // 31. Reason for Seizure
        if (f.includes('reason for seizure') || f.includes('seizure reason') || f === 'reason') {
            return 'seizure_reason';
        }
        // 32. Remarks / Observations
        if (f.includes('remarks') || f.includes('observations') || f.includes('remark')) {
            return 'remark';
        }
        // 33. Mugshots / Facial Biometrics (checked before generic photos so 'Mugshot Photos' matches mugshot)
        if (f.includes('mugshot') || 
            f.includes('front view') || f.includes('left profile') || f.includes('right profile') ||
            f.includes('angle 1') || f.includes('angle 2') || f.includes('angle 3') ||
            f.includes('facial photo') || f.includes('facial') || f.includes('biometric') ||
            f.includes('suspect photo') || f.includes('suspect picture') || f.includes('face photo') ||
            f.includes('face capture') || f.includes('mugshots')) {
            return 'mugshot';
        }

        // 34. Evidence Photos
        if (f.includes('evidence photo') || f.includes('exhibit photo') || f.includes('seizure photo') ||
            f.includes('photos') || f === 'photo') {
            return 'photos';
        }

        // 35. Section 5: Forensic Examination & Laboratory Analysis
        if (f.includes('forensic examination') || f.includes('laboratory analysis') || f.includes('lab analysis') ||
            f.includes('lab examination') || f.includes('section 5') || f.includes('examination information') || f === 'nlr') {
            return 'lab_examination';
        }

        // 36. GSA Upload / Report
        if (f.includes('gsa') || f.includes('gsa report') || f.includes('gsa upload') || f.includes('gsa file')) {
            return 'gsa';
        }

        // 37. Presumptive Test
        if (f.includes('presumptive') || f.includes('screening analysis')) {
            return 'presumptive';
        }

        // 38. Confirmatory Test
        if (f.includes('confirmatory') || f.includes('laboratory verification')) {
            return 'confirmatory';
        }

        // 39. Sample Weight & Metrology
        if (f.includes('sample weight') || f.includes('metrology') || f.includes('sample taken') || f.includes('reserved sample') || f.includes('gross weight')) {
            return 'sample_weight';
        }

        // 40. Screening Methods
        if (f.includes('screening method') || f.includes('instrument used') || f.includes('instrument')) {
            return 'screening_method';
        }

        // 41. Purpose of Examination
        if (f.includes('purpose of examination') || f.includes('examination purpose')) {
            return 'purpose_of_examination';
        }

        // 42. Preliminary Exam Procedure
        if (f.includes('preliminary exam') || f.includes('exam procedure') || f.includes('preliminary procedure')) {
            return 'preliminary_exam_procedure';
        }

        // 43. Test Methods
        if (f.includes('test method') || f.includes('standard protocol')) {
            return 'test_methods';
        }

        // 44. Specifications
        if (f.includes('specification') || f.includes('specifications')) {
            return 'specifications';
        }

        // 45. Disposition
        if (f.includes('disposition') || f.includes('chain of custody')) {
            return 'disposition';
        }

        // 46. Lab Analyst / Examiner
        if (f.includes('analyst') || f.includes('examiner')) {
            return 'lab_analyst';
        }

        // 47. Lab Number / Reference
        if (f.includes('lab number') || f.includes('laboratory number')) {
            return 'lab_number';
        }
        if (f.includes('exam reference') || f.includes('examination reference') || f.includes('analysis reference')) {
            return 'lab_exam_reference';
        }

        // 48. Section 4 General (Seizures / Exhibits / Commodities)
        if (f.includes('section 4') || f.includes('seizures') || f.includes('seizure') || f.includes('exhibits') || f.includes('exhibit manifest') || f.includes('manifest')) {
            return 'seizures';
        }

        return f;
    }

    function isSection5Element(el) {
        if (!el) return false;
        if (el.closest && (el.closest('#labAnalysisExclusiveSections') || el.closest('#section5FormContainer'))) return true;
        const id = (el.id || '').toLowerCase();
        const name = (el.name || '').toLowerCase();
        return id.startsWith('lab') || id.startsWith('presumptive') || id.startsWith('confirmatory') ||
               id.startsWith('sampleweight') || id.startsWith('screeningmethod') ||
               name.startsWith('lab_') || name.startsWith('confirmatory_') || name.startsWith('presumptive_') ||
               name.startsWith('sample_weight') || name.startsWith('screening_method');
    }

    function isGsaElement(el) {
        if (!el) return false;
        if (el.closest && el.closest('#gsaUploadSection')) return true;
        const id = (el.id || '').toLowerCase();
        const name = (el.name || '').toLowerCase();
        return id.startsWith('gsa') || name.startsWith('gsa_') || el.classList.contains('gsa-exhibit-type-input');
    }

    function isSection4Element(el) {
        if (!el) return false;
        if (el.closest && (el.closest('#seizuresDetailsContainer') || el.closest('.seizure-block') || el.closest('#seizuresListContainer'))) return true;
        const id = (el.id || '').toLowerCase();
        return id.startsWith('newseizure') || id.startsWith('devicesearch') || id.startsWith('drugsearch') ||
               id.startsWith('weaponsearch') || id.startsWith('currencysearch') || id.startsWith('ammosearch');
    }

    function getElementKey(el) {
        if (!el) return '';
        const id = (el.id || '').toLowerCase();
        const name = (el.name || '').toLowerCase();
        const dataField = (el.getAttribute('data-field') || '').toLowerCase();

        // 1. Case Title
        if (id === 'casetitleinput' || name === 'title' || name === 'case_title') return 'case_title';

        // 2. Suspect Name
        if (id === 'newarrestedname' || name === 'suspect_name' || name === 'full_name' || dataField === 'name') return 'suspect_name';

        // 3. Alias
        if (id === 'newarrestedalias' || name === 'suspect_alias' || name === 'alias' || dataField === 'alias') return 'suspect_alias';

        // 4. Ghana Card
        if (id === 'newarrestedid' || name === 'ghana_card' || name === 'id_card' || dataField === 'ghana_card') return 'ghana_card';

        // 5. Gender
        if (id === 'newarrestedgender' || id === 'genderdropdowntrigger' || name === 'gender' || dataField === 'gender') return 'gender';

        // 6. DOB & Age
        if (id === 'newarresteddob' || name === 'dob' || dataField === 'dob') return 'dob';
        if (id === 'newarrestedage' || name === 'age' || dataField === 'age') return 'age';

        // 7. Height
        if (id === 'newarrestedheight' || id === 'newarrestedheightunit' || name === 'height' || dataField === 'height') return 'height';

        // 8. Eye Color
        if (id === 'newarrestedeyecolor' || name === 'eye_color' || dataField === 'eye_color') return 'eye_color';

        // 9. POB
        if (id === 'newarrestedpob' || name === 'pob' || dataField === 'pob') return 'pob';

        // 10. Nationality
        if (id === 'newarrestednationality' || id === 'nationalitydropdowntrigger' || name === 'nationality' || dataField === 'nationality') return 'nationality';

        // 11. Address
        if (id === 'newarrestedaddress' || name === 'address' || dataField === 'address') return 'address';

        // 12. Phone
        if (id === 'newarrestedphone' || name === 'phone' || dataField === 'phone' || el.classList.contains('suspect-phone-input')) return 'phone';

        // 13. Marital, Spouse & Partner
        if (id === 'newarrestedmarital' || id === 'maritaldropdowntrigger' || name === 'marital_status' || dataField === 'marital' || dataField === 'marital_status' ||
            id === 'newarrestedspousename' || name === 'spouse_name' || dataField === 'spouse_name' ||
            id === 'newarrestedspousephone' || name === 'spouse_phone' || dataField === 'spouse_phone' ||
            id === 'newarrestedspouseinfo' || name === 'spouse_info' || dataField === 'spouse_info' ||
            id === 'newarrestedpartnername' || name === 'partner_name' || dataField === 'partner_name' ||
            id === 'newarrestedpartnerphone' || name === 'partner_phone' || dataField === 'partner_phone' ||
            id === 'newarrestedpartnerinfo' || name === 'partner_info' || dataField === 'partner_info' ||
            (el.closest && (el.closest('#relationshipDetailsBox') || el.closest('#spouseFieldsCard') || el.closest('#partnerFieldsCard')))) {
            return 'marital';
        }

        // Children / Dependents
        if (id === 'haschildrencheckbox' || id === 'newarrestedchildren' || name === 'children_count' || name === 'has_children' || dataField === 'children' || dataField === 'children_count' || dataField === 'has_children' || (el.closest && el.closest('#childrenInputContainer'))) {
            return 'children';
        }

        // 14. Location
        if (id === 'newarrestedraidlocation' || id === 'newarrestedincidentlocation' || id === 'incidentlocationsearchinput' || name === 'raid_location' || name === 'location' || dataField === 'raid_location') return 'location';

        // 15. Arrest Date & Time
        if (id === 'newarresteddate' || id === 'newarrestedarrestdate' || name === 'arrest_date' || dataField === 'arrest_date') return 'arrest_date';
        if (id === 'newarrestedtime' || id === 'newarrestedarresttime' || name === 'arrest_time' || dataField === 'arrest_time') return 'arrest_time';

        // 16. Facility
        if (id === 'newarrestedfacility' || id === 'facilitysearchinput' || name === 'holding_facility' || name === 'facility' || dataField === 'holding_facility') return 'facility';

        // 17. Offense
        if (id === 'newarrestedoffense' || id === 'offensesearchinput' || name === 'offense_description' || name === 'offense' || dataField === 'offense') return 'offense';

        // 18. Commodity Name
        if (id === 'devicesearchinput' || id === 'newseizurenamedevice' || id === 'drugsearchinput' || id === 'newseizurenamedrug' ||
            id === 'weaponsearchinput' || id === 'newseizurenameweapon' || id === 'currencysearchinput' || id === 'newseizurenamecurrency' ||
            id === 'ammosearchinput' || id === 'newseizurenameammo' || id === 'newseizurename' || id === 'newotherexhibitdescription') return 'commodity_name';

        // 19. Exhibit Category
        if (id === 'newseizurecategory' || id === 'exhibitsearchinput' || name === 'exhibit_category') return 'category';

        // 20-26. Device specs
        if (id === 'newdevmodel' || name === 'dev_model') return 'dev_model';
        if (id === 'newdevserialno' || name === 'dev_serial_no') return 'dev_serial';
        if (id === 'newdevimei1' || id === 'newdevimei2' || name === 'dev_imei1' || name === 'dev_imei2') return 'dev_imei';
        if (id === 'newdevmacaddress' || name === 'dev_mac_address') return 'dev_mac';
        if (id === 'newdevstorage' || name === 'dev_storage') return 'dev_storage';
        if (id === 'newdevcolour' || name === 'dev_colour') return 'dev_colour';
        if (id === 'newdevmarks' || name === 'dev_marks') return 'dev_marks';

        // 27. Concealment
        if (id === 'newseizureconcealment' || name === 'seizure_concealment') return 'concealment';

        // 28-30. Quantity, Weight, Unit, Value
        if (id === 'newseizurequantity' || name === 'seizure_quantity') return 'quantity';
        if (id === 'newseizureweight' || name === 'seizure_weight') return 'weight';
        if (id === 'newseizureunit' || id === 'unitsearchinput' || name === 'seizure_unit') return 'unit';
        if (id === 'newseizurepackaging' || id === 'pkgsearchinput' || name === 'seizure_packaging') return 'packaging';
        if (id === 'newseizurevalue' || name === 'seizure_value') return 'value';

        // 31. Reason
        if (id === 'newseizurereason' || name === 'seizure_reason') return 'seizure_reason';

        // 32. Remark
        if (id === 'newseizureremark' || id === 'newseizureexhibitremark' || name === 'exhibit_remark' || el.classList.contains('newSeizureExhibitRemark')) return 'remark';

        // 33. Photos
        if (id === 'fileseizurephoto' || el.classList.contains('fileSeizurePhoto')) return 'photos';

        // 34. Mugshots
        if (id.startsWith('file-front') || id.startsWith('file-left') || id.startsWith('file-right') ||
            id.startsWith('url-front') || id.startsWith('url-left') || id.startsWith('url-right') ||
            id.startsWith('hidden-front') || id.startsWith('hidden-left') || id.startsWith('hidden-right') ||
            id.startsWith('dropzone-front') || id.startsWith('dropzone-left') || id.startsWith('dropzone-right') ||
            id.startsWith('actions-front') || id.startsWith('actions-left') || id.startsWith('actions-right') ||
            name.includes('mugshot') || id.includes('mugshot') || el.classList.contains('mugshot-input') ||
            (id.startsWith('file-') && (id.includes('front') || id.includes('profile')))) {
            return 'mugshot';
        }

        // 35. Section 5 Lab Analysis & Examination
        if (id === 'labexamreferenceinput' || name === 'lab_exam_reference' || name === 'exam_reference') return 'lab_exam_reference';
        if (id === 'labcasereferenceinput' || name === 'lab_case_reference') return 'lab_case_reference';
        if (id === 'labexhibitnumberinput' || id.startsWith('labcardexhibitno_') || name === 'lab_exhibit_number' || name === 'lab_exhibit_nos[]' || dataField === 'lab_exhibit_no' || el.classList.contains('card-exhibit-no-input')) return 'lab_exhibit_number';
        if (id === 'labexhibitdescriptioninput' || name === 'lab_exhibit_description' || name === 'exhibit_description') return 'lab_exhibit_description';
        if (id === 'labnumberinput' || name === 'laboratory_number') return 'lab_number';
        if (id === 'labanalystinput' || name === 'lab_analyst') return 'lab_analyst';
        if (id === 'labdatereceivedinput' || name === 'lab_date_received') return 'lab_date_received';
        if (id === 'labdatestartedinput' || name === 'lab_date_started') return 'lab_date_started';
        if (id === 'labdatecompletedinput' || name === 'lab_date_completed') return 'lab_date_completed';
        if (id === 'labpurposeofexaminationinput' || name === 'purpose_of_examination') return 'purpose_of_examination';
        if (id === 'labpreliminaryexamprocedureinput' || name === 'preliminary_exam_procedure') return 'preliminary_exam_procedure';
        if (id === 'screeningmethodinput' || name.startsWith('screening_method') || el.classList.contains('screening-method-input')) return 'screening_method';
        if (id === 'confirmatorytestmethods' || name === 'confirmatory_test_methods') return 'test_methods';
        if (id === 'confirmatoryspecifications' || name === 'confirmatory_specifications') return 'specifications';
        if (id === 'confirmatoryremark' || name === 'confirmatory_remark') return 'confirmatory_remark';
        if (id === 'confirmatorydispositionofexhibit' || name === 'disposition_of_exhibit') return 'disposition';

        // 36. Presumptive Test
        if (id.startsWith('presumptivetest') || id.startsWith('presumptivereaction') || el.classList.contains('presumptive-presence-input') || el.classList.contains('presumptive-percentage-input') || el.classList.contains('presumptive-yes-box') || el.classList.contains('presumptive-no-box')) return 'presumptive';

        // 37. Confirmatory Test & Sample Weight
        if (id.startsWith('confirmatorytest') || id.startsWith('confirmatorysubstance') || el.classList.contains('confirmatory-yes-box') || el.classList.contains('confirmatory-no-box') || el.classList.contains('detected-substance-name') || el.classList.contains('detected-substance-purity')) return 'confirmatory';
        if (id.startsWith('sampleweight')) return 'sample_weight';

        // 38. GSA fields
        if (id === 'gsalabnumberinput' || name === 'gsa_lab_number') return 'gsa_lab_number';
        if (id === 'gsacasereferenceinput' || name === 'gsa_case_reference') return 'gsa_case_reference';
        if (id === 'gsastatusinput' || name === 'gsa_status') return 'gsa_status';
        if (id === 'gsareportimageinput' || name.startsWith('gsa_report_images') || el.classList.contains('gsa-exhibit-type-input')) return 'gsa';

        return '';
    }

    function isInputMatchingCorrections(el, corrections) {
        if (!corrections || corrections.length === 0) return { matched: false };
        const elKey = getElementKey(el);
        const elId = (el.id || '').toLowerCase();
        const elName = (el.name || '').toLowerCase();

        for (const c of corrections) {
            const rawField = (c.field || '').trim();
            const corrKey = getCorrectionFieldKey(rawField);

            // 1. Canonical key match
            if (corrKey && elKey && corrKey === elKey) {
                return { matched: true, note: c.note || rawField };
            }

            // 2. Parent-group matching
            if (corrKey === 'lab_examination' && isSection5Element(el)) {
                return { matched: true, note: c.note || rawField };
            }
            if (corrKey === 'gsa' && isGsaElement(el)) {
                return { matched: true, note: c.note || rawField };
            }
            if ((corrKey === 'seizures' || corrKey === 'exhibits' || corrKey === 'manifest') && isSection4Element(el)) {
                return { matched: true, note: c.note || rawField };
            }

            // 3. Direct exact ID or Name match
            const rawLower = rawField.toLowerCase();
            if (rawLower && (rawLower === elId || rawLower === elName)) {
                return { matched: true, note: c.note || rawField };
            }
        }

        return { matched: false };
    }

    function populateFieldValues(payload) {
        if (!payload) return;

        const setVal = (id, val) => {
            if (val === undefined || val === null) return;
            const el = document.getElementById(id);
            if (!el) return;
            if (el.tagName === 'TEXTAREA') {
                el.value = val;
                el.textContent = val;
                el.innerText = val;
            } else if (el.type === 'checkbox') {
                el.checked = !!val;
            } else {
                el.value = val;
            }
        };

        const docketData = (payload.docket_data && typeof payload.docket_data === 'object') ? payload.docket_data : {};
        const docketSeizures = (docketData && Array.isArray(docketData.seizures)) ? docketData.seizures : [];

        // SECTION 1: CASE REGISTRATION & CLASSIFICATION
        setVal('caseTitleInput', payload.title || payload.case_title || docketData.title);
        setVal('labCaseReferenceInput', payload.case_number || payload.case_ref || docketData.case_number);
        
        // Priority (strictly HIGH / LOW)
        const rawPriority = String(payload.priority || docketData.priority || 'HIGH').trim().toUpperCase();
        const priorityVal = ['LOW', 'NORMAL', 'MEDIUM', 'ROUTINE', 'MINOR'].includes(rawPriority) ? 'LOW' : 'HIGH';
        setVal('casePrioritySelect', priorityVal);
        const pLabel = document.getElementById('selectedPriorityLabel');
        if (pLabel) {
            pLabel.textContent = priorityVal === 'LOW' ? 'Low Priority' : 'High Priority';
            pLabel.className = 'font-extrabold text-slate-900 text-xs';
        }

        // Operation Type
        const opVal = payload.operation_type || payload.operation_code || payload.operation_code_name || docketData.operation_code_name;
        if (opVal) {
            setVal('operationTypeSelect', opVal);
            const opLabel = document.getElementById('selectedOpTypeLabel');
            if (opLabel) {
                opLabel.textContent = opVal;
                opLabel.className = 'font-extrabold text-slate-900 text-xs';
            }
        }

        // Case Entry Mode
        const modeVal = payload.suspect_docket_mode || docketData.suspect_docket_mode || 'SINGLE';
        setVal('caseEntryTypeSelect', modeVal);
        const modeLabel = document.getElementById('selectedCaseTypeLabel');
        if (modeLabel) {
            modeLabel.innerText = modeVal === 'MULTIPLE' ? 'Multiple Suspects (Joint Docket)' : 'Single Suspect (Sole Docket)';
            modeLabel.classList.remove('text-slate-500');
            modeLabel.classList.add('text-slate-900');
        }

        // Helper: Resolve any mugshot storage path or data-URI into a valid browser URL
        const resolveMugshotUrl = (val) => {
            if (!val || typeof val !== 'string') return '';
            const trimmed = val.trim();
            if (!trimmed || trimmed === 'null' || trimmed === 'undefined' || trimmed.endsWith('#')) return '';
            if (trimmed.startsWith('data:') || trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
                return trimmed;
            }
            if (trimmed.startsWith('/storage/')) {
                return trimmed;
            }
            if (trimmed.startsWith('storage/')) {
                return '/' + trimmed;
            }
            return '/storage/' + trimmed.replace(/^\/+/, '');
        };

        // SECTION 2: MUGSHOTS
        const mugFront = payload.mugshot_front
            || docketData.mugshot_front
            || payload.mugshots?.front
            || docketData.suspects?.[0]?.mugshot_front
            || docketData.suspects?.[0]?.mugshots?.front
            || payload.suspects?.[0]?.mugshot_front;
        const mugLeft = payload.mugshot_left
            || payload.mugshot_left_profile
            || docketData.mugshot_left
            || payload.mugshots?.left
            || docketData.suspects?.[0]?.mugshot_left
            || docketData.suspects?.[0]?.mugshots?.left
            || payload.suspects?.[0]?.mugshot_left;
        const mugRight = payload.mugshot_right
            || payload.mugshot_right_profile
            || docketData.mugshot_right
            || payload.mugshots?.right
            || docketData.suspects?.[0]?.mugshot_right
            || docketData.suspects?.[0]?.mugshots?.right
            || payload.suspects?.[0]?.mugshot_right;

        const populateMugshot = (angle, src, suspectIdx = null) => {
            if (!src) return;
            const resolvedSrc = resolveMugshotUrl(src);
            if (!resolvedSrc) return;
            const idSuffix = suspectIdx ? `-${angle}-${suspectIdx}` : `-${angle}`;
            const preview = document.getElementById(`preview${idSuffix}`);
            const placeholder = document.getElementById(`placeholder${idSuffix}`);
            const actions = document.getElementById(`actions${idSuffix}`);
            const badge = document.getElementById(`badge${idSuffix}`);
            const hidden = document.getElementById(`hidden${idSuffix}`);
            if (preview) {
                preview.src = resolvedSrc;
                preview.classList.remove('hidden');
            }
            if (placeholder) placeholder.classList.add('hidden');
            if (actions) actions.classList.remove('hidden');
            if (badge) {
                badge.innerText = 'Uploaded';
                badge.className = 'text-[9px] font-mono-code font-bold text-emerald-600 uppercase';
            }
            if (hidden) hidden.value = src;
        };
        populateMugshot('front', mugFront);
        populateMugshot('left-profile', mugLeft);
        populateMugshot('right-profile', mugRight);

        // SECTION 3: SUSPECT BIO-DATA & PERSONAL PARTICULARS
        setVal('newArrestedRaidRef', payload.raid_ref || docketData.raid_ref || 'RAID-2026-0419');
        setVal('newArrestedName', payload.suspect_name || payload.full_name || docketData.full_name);
        setVal('newArrestedAlias', payload.suspect_alias || payload.alias || docketData.alias);
        setVal('newArrestedID', payload.ghana_card || payload.id_card || docketData.id_card);
        
        // Gender
        const genderVal = payload.gender || docketData.gender;
        if (genderVal) {
            setVal('newArrestedGender', genderVal);
            const gLabel = document.getElementById('selectedGenderLabel');
            if (gLabel) {
                gLabel.innerText = genderVal;
                gLabel.classList.remove('text-slate-500');
                gLabel.classList.add('text-slate-900');
            }
            const gIcon = document.getElementById('selectedGenderIcon');
            if (gIcon) {
                const isFem = genderVal.toLowerCase().includes('fem');
                gIcon.innerText = isFem ? '♀' : '♂';
                gIcon.className = `w-5 h-5 rounded-full ${isFem ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700'} flex items-center justify-center text-[10px] font-bold`;
            }
        }

        setVal('newArrestedDOB', payload.dob || docketData.dob);
        setVal('newArrestedAge', payload.age || docketData.age);
        setVal('newArrestedHeight', payload.height || docketData.height);
        setVal('newArrestedHeightUnit', payload.height_unit || docketData.height_unit || 'cm');
        setVal('newArrestedEyeColor', payload.eye_color || docketData.eye_color);
        setVal('newArrestedPOB', payload.pob || docketData.pob);

        // Nationality
        const natVal = payload.nationality || docketData.nationality || 'Ghanaian';
        setVal('newArrestedNationality', natVal);
        const natLabel = document.getElementById('selectedNationalityLabel');
        if (natLabel) {
            natLabel.innerText = natVal;
            natLabel.classList.remove('text-slate-500');
            natLabel.classList.add('text-slate-900');
        }
        const natIcon = document.getElementById('selectedNationalityIcon');
        if (natIcon) {
            natIcon.innerText = natVal === 'Ghanaian' ? 'GHA' : 'NAT';
            natIcon.className = 'w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold';
        }

        setVal('newArrestedAddress', payload.address || docketData.address);
        setVal('newArrestedPhone', payload.phone || docketData.phone);

        // Multi-phones
        const phonesArr = payload.phones || docketData.phones || [];
        if (Array.isArray(phonesArr) && phonesArr.length > 1) {
            for (let i = 1; i < phonesArr.length; i++) {
                const item = phonesArr[i];
                const num = typeof item === 'object' ? item.number : item;
                const lbl = typeof item === 'object' ? item.label : 'Alternate';
                if (num && typeof window.addSuspectPhoneRow === 'function') {
                    const btn = document.querySelector('button[onclick*="addSuspectPhoneRow"]');
                    window.addSuspectPhoneRow(btn, lbl, num);
                }
            }
        }

        // Marital Status
        const maritalVal = payload.marital_status || docketData.marital_status;
        if (maritalVal) {
            setVal('newArrestedMarital', maritalVal);
            const mLabel = document.getElementById('selectedMaritalLabel');
            if (mLabel) {
                mLabel.innerText = maritalVal;
                mLabel.className = 'font-bold text-slate-900 text-xs';
            }
            if (typeof handleMaritalChange === 'function') {
                handleMaritalChange(maritalVal);
            } else {
                const rBox = document.getElementById('relationshipDetailsBox');
                if (rBox && (maritalVal === 'Married' || maritalVal.includes('Relationship'))) {
                    rBox.classList.remove('hidden');
                }
            }
        }

        // Children
        const hasChildren = !!(payload.has_children || (payload.children_count && parseInt(payload.children_count) > 0));
        const childBox = document.getElementById('hasChildrenCheckbox');
        if (childBox) {
            childBox.checked = hasChildren;
            const cContainer = document.getElementById('childrenInputContainer');
            if (cContainer) {
                if (hasChildren) cContainer.classList.remove('hidden');
                else cContainer.classList.add('hidden');
            }
        }
        setVal('newArrestedChildren', payload.children_count || docketData.children_count || '0');

        // Spouse / Partner fields
        setVal('newArrestedSpouseName', payload.spouse_name || docketData.spouse_name);
        setVal('newArrestedSpousePhone', payload.spouse_phone || docketData.spouse_phone);
        setVal('newArrestedSpouseInfo', payload.spouse_info || docketData.spouse_info);
        setVal('newArrestedPartnerName', payload.partner_name || docketData.partner_name);
        setVal('newArrestedPartnerPhone', payload.partner_phone || docketData.partner_phone);
        setVal('newArrestedPartnerInfo', payload.partner_info || docketData.partner_info);

        // Place of Arrest / Raid Location
        const raidLoc = payload.raid_location || payload.location || docketData.raid_location || docketData.location;
        setVal('newArrestedRaidLocation', raidLoc);
        setVal('newArrestedIncidentLocation', raidLoc);
        setVal('incidentLocationSearchInput', raidLoc);

        // GPS Coordinates & Radar Badge
        const lat = payload.latitude || docketData.latitude;
        const lng = payload.longitude || docketData.longitude;
        setVal('incidentLatitude', lat);
        setVal('incidentLongitude', lng);

        if (lat && lng) {
            const numLat = parseFloat(lat);
            const numLng = parseFloat(lng);
            if (!isNaN(numLat) && !isNaN(numLng)) {
                const latStr = Math.abs(numLat).toFixed(6);
                const lngStr = Math.abs(numLng).toFixed(6);
                const latHem = numLat >= 0 ? 'N' : 'S';
                const lngHem = numLng >= 0 ? 'E' : 'W';
                const gpsBadge = document.getElementById('incidentGpsBadge') || document.getElementById('gpsBadge');
                if (gpsBadge) {
                    gpsBadge.innerHTML = `<span class="text-emerald-700 font-extrabold">📍 ${latStr}° ${latHem}, ${lngStr}° ${lngHem}</span>`;
                    gpsBadge.classList.remove('bg-slate-100', 'text-slate-500');
                    gpsBadge.classList.add('bg-emerald-50', 'text-emerald-900', 'border-emerald-300');
                }
                const statusDot = document.getElementById('incidentMapStatusDot');
                if (statusDot) {
                    statusDot.classList.remove('bg-slate-400');
                    statusDot.classList.add('bg-emerald-500');
                }
                const statusPing = document.getElementById('incidentMapStatusPing');
                if (statusPing) statusPing.classList.remove('hidden');
                const srcBadge = document.getElementById('incidentMapSourceBadge');
                if (srcBadge) srcBadge.classList.remove('hidden');
            }
        }

        // Arrest Date & Time
        const arrestDate = payload.arrest_date || docketData.arrest_date;
        const arrestTime = payload.arrest_time || docketData.arrest_time;
        setVal('newArrestedDate', arrestDate);
        setVal('newArrestedArrestDate', arrestDate);
        setVal('newArrestedTime', arrestTime);
        setVal('newArrestedArrestTime', arrestTime);

        // Custody Holding Facility
        const facVal = payload.holding_facility || payload.facility || docketData.holding_facility || docketData.facility;
        setVal('newArrestedFacility', facVal);
        setVal('facilitySearchInput', facVal);

        // Brief Facts / Offense Description
        const offVal = payload.offense_description || payload.offense || payload.offense_type || docketData.offense_description || docketData.offense;
        setVal('newArrestedOffense', offVal);
        setVal('offenseSearchInput', offVal);

        // Officers
        setVal('newArrestedArrestingOfficer', payload.arresting_officer || payload.submitting_officer || docketData.arresting_officer);
        setVal('newArrestedInvestigatingOfficer', payload.investigating_officer || docketData.investigating_officer);

        // SECTION 4: SEIZURES (COMMODITIES)
        const hasSeizures = !!(payload.has_seizures || payload.exhibit_category || payload.seizure_name || payload.commodity_name || (docketSeizures.length > 0) || (Array.isArray(payload.seizures) && payload.seizures.length > 0) || (Array.isArray(payload.seizuresList) && payload.seizuresList.length > 0));
        const seizureChk = document.getElementById('hasSeizuresCheckbox');
        const seizureCont = document.getElementById('seizuresDetailsContainer');
        if (seizureChk) seizureChk.checked = hasSeizures;
        if (hasSeizures && seizureCont) {
            seizureCont.classList.remove('hidden');
        }

        if (hasSeizures) {
            const seizureItems = (Array.isArray(payload.seizures) && payload.seizures.length > 0)
                ? payload.seizures
                : ((Array.isArray(payload.seizuresList) && payload.seizuresList.length > 0)
                    ? payload.seizuresList
                    : (docketSeizures.length > 0 ? docketSeizures : [payload]));

            const seizureCountInput = document.getElementById('seizureCountInput');
            if (seizureCountInput) {
                seizureCountInput.value = seizureItems.length;
            }

            const primarySuspectName = payload.suspect_name || payload.full_name || docketData.full_name || '';

            seizureItems.forEach((rawItem, idx) => {
                let block = document.querySelectorAll('.seizure-block')[idx];
                if (!block && idx > 0 && typeof addSeizureBlock === 'function') {
                    addSeizureBlock();
                    block = document.querySelectorAll('.seizure-block')[idx];
                }
                if (!block) return;

                const dItem = docketSeizures[idx] || {};
                const item = Object.assign({}, payload, dItem, rawItem);

                const cat = item.exhibit_category || item.category || dItem.exhibit_category || payload.exhibit_category || 'Electronic Devices';
                const setBlockVal = (selector, val) => {
                    if (val === undefined || val === null) return;
                    const elements = block.querySelectorAll(selector);
                    elements.forEach(el => {
                        if (el.tagName === 'TEXTAREA') {
                            el.value = val;
                            el.textContent = val;
                            el.innerText = val;
                        } else if (el.type === 'checkbox') {
                            el.checked = !!val;
                        } else {
                            el.value = val;
                        }
                    });
                    if (selector.startsWith('#') && (!elements || elements.length === 0)) {
                        const docEl = document.getElementById(selector.slice(1));
                        if (docEl) {
                            if (docEl.tagName === 'TEXTAREA') {
                                docEl.value = val;
                                docEl.textContent = val;
                                docEl.innerText = val;
                            } else if (docEl.type === 'checkbox') {
                                docEl.checked = !!val;
                            } else {
                                docEl.value = val;
                            }
                        }
                    }
                };

                setBlockVal('#newSeizureCategory', cat);
                setBlockVal('#exhibitSearchInput', cat);
                if (typeof updateCommodityField === 'function') {
                    updateCommodityField(cat, block);
                }

                // Explicitly unhide / hide panels according to category to guarantee visibility
                const devWrapper = block.querySelector('#deviceSelectWrapper');
                const devFields = block.querySelector('#deviceFieldsWrapper');
                const drugWrapper = block.querySelector('#drugSelectWrapper');
                const wpnWrapper = block.querySelector('#weaponSelectWrapper');
                const wpnFields = block.querySelector('#weaponFieldsWrapper');
                const curWrapper = block.querySelector('#currencySelectWrapper');
                const ammoWrapper = block.querySelector('#ammoSelectWrapper');
                const ammoFields = block.querySelector('#ammoFieldsWrapper');
                const vehWrapper = block.querySelector('#vehicleFieldsWrapper');
                const otherWrapper = block.querySelector('#otherExhibitDescriptionWrapper, .other-exhibit-description-wrapper');
                const commTextWrapper = block.querySelector('#commodityTextWrapper');

                if (cat === 'Electronic Devices') {
                    if (devWrapper) devWrapper.classList.remove('hidden');
                    if (devFields) devFields.classList.remove('hidden');
                    if (commTextWrapper) commTextWrapper.classList.add('hidden');
                } else if (cat === 'Drugs') {
                    if (drugWrapper) drugWrapper.classList.remove('hidden');
                    if (commTextWrapper) commTextWrapper.classList.add('hidden');
                } else if (cat === 'Weapons') {
                    if (wpnWrapper) wpnWrapper.classList.remove('hidden');
                    if (wpnFields) wpnFields.classList.remove('hidden');
                    if (commTextWrapper) commTextWrapper.classList.add('hidden');
                } else if (cat === 'Money') {
                    if (curWrapper) curWrapper.classList.remove('hidden');
                    if (commTextWrapper) commTextWrapper.classList.add('hidden');
                } else if (cat === 'Ammunition') {
                    if (ammoWrapper) ammoWrapper.classList.remove('hidden');
                    if (ammoFields) ammoFields.classList.remove('hidden');
                    if (commTextWrapper) commTextWrapper.classList.add('hidden');
                } else if (cat === 'Vehicles') {
                    if (vehWrapper) vehWrapper.classList.remove('hidden');
                    if (commTextWrapper) commTextWrapper.classList.remove('hidden');
                } else if (cat === 'Other Exhibits' || cat === 'Other' || cat.toLowerCase().includes('other')) {
                    if (otherWrapper) otherWrapper.classList.remove('hidden');
                    if (commTextWrapper) commTextWrapper.classList.remove('hidden');
                } else {
                    if (commTextWrapper) commTextWrapper.classList.remove('hidden');
                }

                // Name / Commodity
                const commName = item.commodity_name || item.seizure_name || item.seizure_name_device || item.seizure_name_drug || item.seizure_name_weapon || item.seizure_name_currency || item.seizure_name_ammo || dItem.commodity_name || dItem.seizure_name || payload.commodity_name || payload.seizure_name || '';
                setBlockVal('#deviceSearchInput', commName);
                setBlockVal('#newSeizureNameDevice', commName);
                setBlockVal('#drugSearchInput', commName);
                setBlockVal('#newSeizureNameDrug', commName);
                setBlockVal('#weaponSearchInput', commName);
                setBlockVal('#newSeizureNameWeapon', commName);
                setBlockVal('#currencySearchInput', commName);
                setBlockVal('#newSeizureNameCurrency', commName);
                setBlockVal('#ammoSearchInput', commName);
                setBlockVal('#newSeizureNameAmmo', commName);
                setBlockVal('#newSeizureName', commName);
                setBlockVal('#newOtherExhibitDescription', item.other_exhibit_description || payload.other_exhibit_description || commName);
                setBlockVal('.other-exhibit-description-input', item.other_exhibit_description || payload.other_exhibit_description || commName);

                // Suspect / Ownership Association
                const ownershipSelect = block.querySelector('.seizure-associated-person-select');
                if (ownershipSelect && primarySuspectName) {
                    let hasSuspectOption = false;
                    for (let optIdx = 0; optIdx < ownershipSelect.options.length; optIdx++) {
                        if (ownershipSelect.options[optIdx].value === primarySuspectName) {
                            hasSuspectOption = true;
                            break;
                        }
                    }
                    if (!hasSuspectOption) {
                        const opt = document.createElement('option');
                        opt.value = primarySuspectName;
                        opt.textContent = `${primarySuspectName} (Primary Suspect)`;
                        ownershipSelect.appendChild(opt);
                    }
                    const assignedOwnership = item.associated_person || item.suspect_ownership || item.suspect_name || dItem.associated_person || dItem.suspect_ownership || payload.associated_person || primarySuspectName;
                    ownershipSelect.value = assignedOwnership;
                }

                // Device Fields
                setBlockVal('#newDevModel', item.dev_model || dItem.dev_model || payload.dev_model);
                setBlockVal('#newDevSerialNo', item.dev_serial_no || dItem.dev_serial_no || payload.dev_serial_no);
                setBlockVal('#newDevImei1', item.dev_imei1 || dItem.dev_imei1 || payload.dev_imei1);
                setBlockVal('#newDevImei2', item.dev_imei2 || dItem.dev_imei2 || payload.dev_imei2);
                setBlockVal('#newDevMacAddress', item.dev_mac_address || dItem.dev_mac_address || payload.dev_mac_address);
                setBlockVal('#newDevStorage', item.dev_storage || dItem.dev_storage || payload.dev_storage);
                setBlockVal('#newDevColour', item.dev_colour || dItem.dev_colour || payload.dev_colour);
                setBlockVal('#newDevMarks', item.dev_marks || dItem.dev_marks || payload.dev_marks);

                // Weapon Fields
                setBlockVal('#wpnModel', item.wpn_model || payload.wpnModel || payload.wpn_model);
                setBlockVal('#wpnSerialNo', item.wpn_serial_no || payload.wpnSerialNo || payload.wpn_serial_no);
                setBlockVal('#wpnCalibre', item.wpn_calibre || payload.wpnCalibre || payload.wpn_calibre);
                setBlockVal('#wpnColour', item.wpn_colour || payload.wpnColour || payload.wpn_colour);
                setBlockVal('#wpnSize', item.wpn_size || payload.wpnSize || payload.wpn_size);
                setBlockVal('#wpnMarks', item.wpn_marks || payload.wpnMarks || payload.wpn_marks);
                setBlockVal('#wpnLocationFound', item.wpn_location_found || payload.wpnLocationFound || payload.wpn_location_found);
                setBlockVal('#wpnLoadedStatus', item.wpn_loaded_status || payload.wpnLoadedStatus || payload.wpn_loaded_status);
                setBlockVal('#wpnCondition', item.wpn_condition || payload.wpnCondition || payload.wpn_condition);

                // Vehicle Fields
                setBlockVal('#vehMake', item.vehMake || payload.vehMake || item.veh_make || payload.veh_make);
                setBlockVal('#vehModel', item.vehModel || payload.vehModel || item.veh_model || payload.veh_model);
                setBlockVal('#vehType', item.vehType || payload.vehType || item.veh_type || payload.veh_type);
                setBlockVal('#vehYear', item.vehYear || payload.vehYear || item.veh_year || payload.veh_year);
                setBlockVal('#vehColour', item.vehColour || payload.vehColour || item.veh_colour || payload.veh_colour);
                setBlockVal('#vehRegNo', item.vehRegNo || payload.vehRegNo || item.veh_reg_no || payload.veh_reg_no);
                setBlockVal('#vehVIN', item.vehVIN || payload.vehVIN || item.veh_vin || payload.veh_vin);
                setBlockVal('#vehEngineNo', item.vehEngineNo || payload.vehEngineNo || item.veh_engine_no || payload.veh_engine_no);
                setBlockVal('#vehMods', item.vehMods || payload.vehMods || item.veh_mods || payload.veh_mods);
                setBlockVal('#vehFeatures', item.vehFeatures || payload.vehFeatures || item.veh_features || payload.veh_features);

                // Ammunition Fields
                setBlockVal('#ammoCartridgeAppearance', item.ammo_cartridge_appearance || payload.ammo_cartridge_appearance || payload.ammoCartridgeAppearance);
                setBlockVal('#ammoProjectileType', item.ammo_projectile_type || payload.ammo_projectile_type || payload.ammoProjectileType);
                setBlockVal('#ammoCaseMaterial', item.ammo_case_material || payload.ammo_case_material || payload.ammoCaseMaterial);
                setBlockVal('#ammoColourMarkings', item.ammo_colour_markings || payload.ammo_colour_markings || payload.ammoColourMarkings);
                setBlockVal('#ammoCondition', item.ammo_condition || payload.ammo_condition || payload.ammoCondition);
                setBlockVal('#ammoOtherCondition', item.ammo_other_condition || payload.ammo_other_condition || payload.ammoOtherCondition);
                setBlockVal('#ammoPackagingType', item.ammo_packaging_type || payload.ammo_packaging_type || payload.ammoPackagingType);
                setBlockVal('#ammoSealCondition', item.ammo_seal_condition || payload.ammo_seal_condition || payload.ammoSealCondition);
                setBlockVal('#ammoPackageReferenceNumber', item.ammo_package_reference_number || payload.ammo_package_reference_number || payload.ammoPackageReferenceNumber);
                setBlockVal('#ammoConditionWhenReceived', item.ammo_condition_when_received || payload.ammo_condition_when_received || payload.ammoConditionWhenReceived);

                // Metrics & Seizure Particulars
                setBlockVal('#newSeizureQuantity', item.seizure_quantity || item.quantity || dItem.seizure_quantity || payload.seizure_quantity);
                setBlockVal('#newSeizurePackaging', item.seizure_packaging || item.packaging || dItem.seizure_packaging || payload.seizure_packaging);
                setBlockVal('#pkgSearchInput', item.seizure_packaging || item.packaging || dItem.seizure_packaging || payload.seizure_packaging);
                setBlockVal('#newSeizureWeight', item.seizure_weight || item.weight || dItem.seizure_weight || payload.seizure_weight);
                setBlockVal('#newSeizureUnit', item.seizure_unit || item.unit || dItem.seizure_unit || payload.seizure_unit);
                setBlockVal('#unitSearchInput', item.seizure_unit || item.unit || dItem.seizure_unit || payload.seizure_unit);
                setBlockVal('#newSeizureValue', item.seizure_value || item.valuation || dItem.seizure_value || payload.seizure_value || payload.valuation);
                setBlockVal('#newSeizureConcealment', item.seizure_concealment || item.concealment || dItem.seizure_concealment || payload.seizure_concealment);
                setBlockVal('#newSeizureDate', item.seizure_date || item.date_seized || dItem.seizure_date || payload.seizure_date);
                setBlockVal('#newSeizureTime', item.seizure_time || item.time_seized || dItem.seizure_time || payload.seizure_time);
                setBlockVal('#newSeizureLocation', item.seizure_location || item.location || dItem.seizure_location || payload.seizure_location);
                setBlockVal('.seizure-location-input', item.seizure_location || item.location || dItem.seizure_location || payload.seizure_location);
                
                // Reason for Seizure
                const reasonVal = item.seizure_reason || item.reason || dItem.seizure_reason || payload.seizure_reason || '';
                setBlockVal('#newSeizureReason', reasonVal);
                setBlockVal('textarea[name="seizure_reason"]', reasonVal);
                
                // Remarks / Observations
                const remarkVal = item.newSeizureRemark || item.exhibit_remark || item.remark || dItem.exhibit_remark || payload.newSeizureRemark || payload.exhibit_remark || payload.remark || '';
                setBlockVal('#newSeizureRemark', remarkVal);
                setBlockVal('.newSeizureExhibitRemark', remarkVal);
                setBlockVal('.seizure-remark-input', remarkVal);
                setBlockVal('textarea[name="exhibit_remark"]', remarkVal);

                // Vault Allocation & Exhibit Classification
                setBlockVal('.exhibit-type-select', item.exhibit_type || dItem.exhibit_type || payload.exhibit_type);
                setBlockVal('.drug-classification-select', item.drug_classification || dItem.drug_classification || payload.drug_classification);

                // Seizing Officers
                const offStr = item.seizure_officer || dItem.seizure_officer || payload.seizure_officer || '';
                const offNameInput = block.querySelector('.seizure-officer-name') || document.querySelector('.seizure-officer-name');
                const offIdInput = block.querySelector('.seizure-officer-service-id') || document.querySelector('.seizure-officer-service-id');
                const offMatch = offStr ? offStr.match(/^(.*?)\s*\((.*?)\)$/) : null;
                if (offMatch) {
                    if (offNameInput) offNameInput.value = offMatch[1].trim();
                    if (offIdInput) offIdInput.value = offMatch[2].trim();
                } else {
                    if (offNameInput) offNameInput.value = offStr;
                    if (offIdInput && (item.seizure_officer_service_id || dItem.seizure_officer_service_id || payload.seizure_officer_service_id)) {
                        offIdInput.value = item.seizure_officer_service_id || dItem.seizure_officer_service_id || payload.seizure_officer_service_id;
                    }
                }

                setBlockVal('#newSeizureInvestigatingOfficer', item.investigating_officer || payload.investigating_officer);
                setBlockVal('#newSeizureSourceRequestingUnit', item.source_requesting_unit || payload.source_requesting_unit);
                setBlockVal('#newSeizureSourceOfExhibits', item.source_of_exhibits || payload.source_of_exhibits);

                // Evidence Photos (Exhibits)
                const photos = (Array.isArray(item.evidence_photos) && item.evidence_photos.length > 0)
                    ? item.evidence_photos
                    : ((dItem && Array.isArray(dItem.evidence_photos) && dItem.evidence_photos.length > 0)
                        ? dItem.evidence_photos
                        : ((Array.isArray(payload.evidence_photos) && payload.evidence_photos.length > 0)
                            ? payload.evidence_photos
                            : ((docketData && Array.isArray(docketData.evidence_photos) && docketData.evidence_photos.length > 0)
                                ? docketData.evidence_photos
                                : [])));

                if (Array.isArray(photos) && photos.length > 0) {
                    block._seizurePhotos = [...photos];
                    window.seizedExhibitDataUrls = [...photos];

                    const grid = block.querySelector('#seizurePhotosGrid, .seizurePhotosGrid, .seizure-photos-grid') || document.querySelector('#seizurePhotosGrid, .seizurePhotosGrid');
                    const countBadge = block.querySelector('#seizurePhotosCount, .seizurePhotosCount, .seizure-photos-count') || document.querySelector('#seizurePhotosCount, .seizurePhotosCount');
                    
                    if (grid) {
                        grid.classList.remove('hidden');
                        grid.style.display = 'grid';
                        grid.innerHTML = photos.map((p, pIdx) => `
                            <div class="relative group rounded-xl overflow-hidden border border-slate-200 aspect-square bg-slate-100 shadow-2xs seizure-photo-item">
                                <img src="${p}" alt="Evidence Photo ${pIdx + 1}" class="w-full h-full object-cover">
                                <span class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-slate-900/80 text-white text-[9px] font-mono-code font-bold rounded">#${pIdx + 1}</span>
                            </div>
                        `).join('');
                    }
                    if (countBadge) {
                        countBadge.classList.remove('hidden');
                        countBadge.style.display = 'inline-flex';
                        countBadge.innerText = `${photos.length} Photo${photos.length > 1 ? 's' : ''} Uploaded`;
                    }
                }
            });
        }

        // MULTIPLE SUSPECTS RE-HYDRATION
        if (Array.isArray(payload.suspects) && payload.suspects.length > 1) {
            for (let sIdx = 1; sIdx < payload.suspects.length; sIdx++) {
                const sData = payload.suspects[sIdx];
                let card = document.getElementById(`suspectCard-${sIdx + 1}`);
                if (!card && typeof addSuspectDetailsRow === 'function') {
                    addSuspectDetailsRow();
                    card = document.getElementById(`suspectCard-${sIdx + 1}`);
                }
                if (card) {
                    const setCardVal = (field, val) => {
                        if (val === undefined || val === null) return;
                        const el = card.querySelector(`[data-field="${field}"]`);
                        if (el) {
                            if (el.tagName === 'TEXTAREA') {
                                el.value = val;
                                el.textContent = val;
                                el.innerText = val;
                            } else {
                                el.value = val;
                            }
                        }
                    };
                    setCardVal('name', sData.suspect_name || sData.name || sData.full_name);
                    setCardVal('alias', sData.suspect_alias || sData.alias);
                    setCardVal('ghana_card', sData.ghana_card || sData.id_card);
                    setCardVal('gender', sData.gender);
                    setCardVal('dob', sData.dob);
                    setCardVal('age', sData.age);
                    setCardVal('height', sData.height);
                    setCardVal('eye_color', sData.eye_color);
                    setCardVal('nationality', sData.nationality);
                    setCardVal('pob', sData.pob);
                    setCardVal('address', sData.address);
                    setCardVal('phone', sData.phone);
                    setCardVal('marital', sData.marital_status || sData.marital);
                    setCardVal('offense', sData.offense_description || sData.offense);
                    setCardVal('raid_location', sData.raid_location || sData.location);
                    setCardVal('holding_facility', sData.holding_facility);
                    setCardVal('arrest_date', sData.arrest_date);
                    setCardVal('arrest_time', sData.arrest_time);

                    const coMugFront = sData.mugshot_front || sData.mugshots?.front || sData.photo;
                    const coMugLeft = sData.mugshot_left || sData.mugshots?.left;
                    const coMugRight = sData.mugshot_right || sData.mugshots?.right;
                    if (coMugFront) populateMugshot('front', coMugFront, sIdx + 1);
                    if (coMugLeft) populateMugshot('left-profile', coMugLeft, sIdx + 1);
                    if (coMugRight) populateMugshot('right-profile', coMugRight, sIdx + 1);
                }
            }
        }

        // SECTION 5: FORENSIC EXAMINATION & LABORATORY ANALYSIS (OR GSA REPORT)
        const isLabView = document.getElementById('labAnalysisExclusiveSections') !== null || document.getElementById('gsaUploadSection') !== null;
        if (isLabView) {
            const labExamData = (docketData && typeof docketData.lab_examination === 'object') ? docketData.lab_examination : (payload.lab_examination || {});
            
            // Determine active workflow mode: 'gsa' or 'nlr'
            let labMode = payload.lab_workflow_mode || docketData.lab_workflow_mode || '';
            if (!labMode) {
                if (payload.gsa_lab_number || docketData.gsa_lab_number || (Array.isArray(payload.gsa_report_images) && payload.gsa_report_images.length > 0)) {
                    labMode = 'gsa';
                } else if (payload.lab_exam_reference || labExamData.exam_reference || payload.laboratory_number || labExamData.laboratory_number) {
                    labMode = 'nlr';
                } else {
                    labMode = 'nlr';
                }
            }
            window.activeLabWorkflowMode = labMode;

            const nlrSec = document.getElementById('labAnalysisExclusiveSections');
            const gsaSec = document.getElementById('gsaUploadSection');
            const gsaBtn = document.getElementById('toggleGsaReportBtn');
            const nlrBtn = document.getElementById('toggleNlrReportBtn');

            if (labMode === 'gsa') {
                if (gsaSec) gsaSec.classList.remove('hidden');
                if (nlrSec) nlrSec.classList.add('hidden');
                if (gsaBtn) {
                    gsaBtn.classList.add('ring-2', 'ring-blue-400');
                    gsaBtn.classList.remove('opacity-40');
                }
                if (nlrBtn) {
                    nlrBtn.classList.remove('ring-2', 'ring-emerald-400');
                    nlrBtn.classList.add('opacity-40');
                }

                // Populate GSA fields
                setVal('gsaLabNumberInput', payload.gsa_lab_number || docketData.gsa_lab_number || labExamData.gsa_lab_number);
                setVal('gsaCaseReferenceInput', payload.gsa_case_reference || docketData.gsa_case_reference || labExamData.gsa_case_reference);
                setVal('gsaStatusInput', payload.gsa_status || docketData.gsa_status || labExamData.gsa_status);

                const gsaExhTypes = payload.exhibit_type || docketData.gsa_exhibit_types || (Array.isArray(payload.gsa_exhibit_types) ? payload.gsa_exhibit_types : []);
                if (Array.isArray(gsaExhTypes) && gsaExhTypes.length > 0) {
                    const typeInputs = document.querySelectorAll('.gsa-exhibit-type-input');
                    gsaExhTypes.forEach((tVal, tIdx) => {
                        if (typeInputs[tIdx]) {
                            typeInputs[tIdx].value = tVal;
                        } else if (typeof addGsaExhibitTypeInput === 'function') {
                            addGsaExhibitTypeInput();
                            const updatedInputs = document.querySelectorAll('.gsa-exhibit-type-input');
                            if (updatedInputs[tIdx]) updatedInputs[tIdx].value = tVal;
                        }
                    });
                }
            } else {
                // NLR MODE
                if (nlrSec) nlrSec.classList.remove('hidden');
                if (gsaSec) gsaSec.classList.add('hidden');
                if (nlrBtn) {
                    nlrBtn.classList.add('ring-2', 'ring-emerald-400');
                    nlrBtn.classList.remove('opacity-40');
                }
                if (gsaBtn) {
                    gsaBtn.classList.remove('ring-2', 'ring-blue-400');
                    gsaBtn.classList.add('opacity-40');
                }

                // Synchronize Section 4 exhibits into Section 5 manifest cards & test containers
                if (typeof syncSection4ExhibitsToLabSection === 'function') {
                    syncSection4ExhibitsToLabSection();
                }

                // Populate Section 5 main fields
                setVal('labExamReferenceInput', payload.lab_exam_reference || payload.exam_reference || labExamData.exam_reference || labExamData.lab_exam_reference);
                setVal('labCaseReferenceInput', payload.lab_case_reference || payload.case_reference || docketData.case_number || payload.case_number);
                setVal('labExhibitNumberInput', payload.lab_exhibit_number || payload.lab_exhibit_no || labExamData.lab_exhibit_number);
                setVal('labExhibitDescriptionInput', payload.lab_exhibit_description || payload.exhibit_description || labExamData.lab_exhibit_description);
                setVal('labNumberInput', payload.laboratory_number || payload.lab_number || labExamData.laboratory_number);
                setVal('labAnalystInput', payload.lab_analyst || labExamData.lab_analyst);
                setVal('labDateReceivedInput', payload.lab_date_received || labExamData.lab_date_received);
                setVal('labDateStartedInput', payload.lab_date_started || labExamData.lab_date_started);
                setVal('labDateCompletedInput', payload.lab_date_completed || labExamData.lab_date_completed);
                setVal('labPurposeOfExaminationInput', payload.purpose_of_examination || labExamData.purpose_of_examination);
                setVal('labPreliminaryExamProcedureInput', payload.preliminary_exam_procedure || labExamData.preliminary_exam_procedure);
                setVal('confirmatoryTestMethods', payload.confirmatory_test_methods || payload.test_methods || labExamData.test_methods);
                setVal('confirmatorySpecifications', payload.confirmatory_specifications || payload.specifications || labExamData.specifications);
                setVal('confirmatoryRemark', payload.confirmatory_remark || payload.remark || labExamData.remark);
                setVal('confirmatoryDispositionOfExhibit', payload.disposition_of_exhibit || labExamData.disposition_of_exhibit);

                // Screening methods
                const screeningMethods = payload.screening_method || labExamData.screening_method || [];
                if (Array.isArray(screeningMethods) && screeningMethods.length > 0) {
                    screeningMethods.forEach((methodVal, mIdx) => {
                        let methodInputs = document.querySelectorAll('.screening-method-input');
                        if (methodInputs[mIdx]) {
                            methodInputs[mIdx].value = methodVal;
                        } else if (typeof addScreeningMethodRow === 'function') {
                            addScreeningMethodRow();
                            methodInputs = document.querySelectorAll('.screening-method-input');
                            if (methodInputs[mIdx]) methodInputs[mIdx].value = methodVal;
                        }
                    });
                } else if (typeof screeningMethods === 'string' && screeningMethods.trim() !== '') {
                    setVal('screeningMethodInput', screeningMethods);
                }

                // Populate per-exhibit card data, presumptive reactions, and confirmatory tests
                const seizureItems = (Array.isArray(payload.seizures) && payload.seizures.length > 0)
                    ? payload.seizures
                    : ((Array.isArray(payload.seizuresList) && payload.seizuresList.length > 0)
                        ? payload.seizuresList
                        : (docketSeizures.length > 0 ? docketSeizures : [payload]));

                seizureItems.forEach((rawItem, sIdx) => {
                    const itemNum = sIdx + 1;
                    const dItem = docketSeizures[sIdx] || {};
                    const item = Object.assign({}, payload, dItem, rawItem);

                    // Exhibit Card Number
                    const exhNo = item.lab_exhibit_no || item.lab_exhibit_number || (Array.isArray(payload.lab_exhibit_nos) ? payload.lab_exhibit_nos[sIdx] : '') || '';
                    const cardInp = document.getElementById(`labCardExhibitNo_${itemNum}`);
                    if (cardInp && exhNo) {
                        cardInp.value = exhNo;
                    }

                    // Presumptive Result
                    const presResult = ((item.presumptive_result || item.presumptive_test_reaction || payload.presumptive_result || labExamData.presumptive_result || 'NO') + '').toUpperCase();
                    const presYesBox = document.getElementById(`presumptiveTestYes_${itemNum}`);
                    const presNoBox = document.getElementById(`presumptiveTestNo_${itemNum}`);
                    const presCont = document.getElementById(`presumptiveReactionDetailsContainer_${itemNum}`);
                    const presInp = document.getElementById(`presumptiveReactionResultInput_${itemNum}`);
                    if (presResult === 'YES') {
                        if (presYesBox) presYesBox.checked = true;
                        if (presNoBox) presNoBox.checked = false;
                        if (presCont) presCont.classList.remove('hidden');
                        if (presInp) presInp.value = 'YES';

                        const presence = item.presumptive_presence || payload.presumptive_presence || labExamData.presumptive_presence || '';
                        const percent = item.presumptive_percentage || payload.presumptive_percentage || labExamData.presumptive_percentage || '';
                        const presenceInput = presCont?.querySelector('.presumptive-presence-input');
                        const percentInput = presCont?.querySelector('.presumptive-percentage-input');
                        if (presenceInput && presence) presenceInput.value = presence;
                        if (percentInput && percent) percentInput.value = percent;
                    } else {
                        if (presYesBox) presYesBox.checked = false;
                        if (presNoBox) presNoBox.checked = true;
                        if (presCont) presCont.classList.add('hidden');
                        if (presInp) presInp.value = 'NO';
                    }

                    // Confirmatory Result
                    const confResult = ((item.confirmatory_test_result || item.confirmatory_result || payload.confirmatory_test_result || payload.confirmatory_result || labExamData.confirmatory_result || 'NO') + '').toUpperCase();
                    const confYesBox = document.getElementById(`confirmatoryTestYes_${itemNum}`);
                    const confNoBox = document.getElementById(`confirmatoryTestNo_${itemNum}`);
                    const confCont = document.getElementById(`sampleWeightContainer_${itemNum}`);
                    const confInp = document.getElementById(`confirmatoryTestResultInput_${itemNum}`);
                    if (confResult === 'YES') {
                        if (confYesBox) confYesBox.checked = true;
                        if (confNoBox) confNoBox.checked = false;
                        if (confCont) confCont.classList.remove('hidden');
                        if (confInp) confInp.value = 'YES';

                        const taken = item.sample_weight_taken || item.sample_taken || payload.sample_weight_taken || payload.sample_taken || labExamData.sample_taken || '';
                        const takenInp = document.getElementById(`sampleWeightTaken_${itemNum}`);
                        if (takenInp && taken) {
                            takenInp.value = taken;
                            if (typeof updateSampleWeightReservedForExhibit === 'function') {
                                updateSampleWeightReservedForExhibit(itemNum);
                            }
                        }
                        const reserved = item.sample_weight_reserved || item.sample_reserved || payload.sample_weight_reserved || payload.sample_reserved || labExamData.sample_reserved || '';
                        const resInp = document.getElementById(`sampleWeightReserved_${itemNum}`);
                        if (resInp && reserved) resInp.value = reserved;

                        const detSubs = item.detected_substances || payload.detected_substances || labExamData.detected_substances;
                        if (Array.isArray(detSubs) && detSubs.length > 0) {
                            const listContainer = document.getElementById(`detectedSubstancesListContainer_${itemNum}`);
                            if (listContainer) {
                                listContainer.innerHTML = '';
                                detSubs.forEach(sub => {
                                    if (typeof addDetectedSubstanceRowForExhibit === 'function') {
                                        addDetectedSubstanceRowForExhibit(itemNum, sub.name || sub.substance || '', sub.purity || sub.percentage || '');
                                    }
                                });
                            }
                        }
                    } else {
                        if (confYesBox) confYesBox.checked = false;
                        if (confNoBox) confNoBox.checked = true;
                        if (confCont) confCont.classList.add('hidden');
                        if (confInp) confInp.value = 'NO';
                    }
                });
            }
        }
    }

    function applyRollbackDocketMode() {
        const payload = window.ROLLBACK_PAYLOAD || {};
        const corrections = window.ROLLBACK_CORRECTIONS || [];
        const form = document.getElementById('arrestDocketForm');
        if (!form) return;

        // 1. Hide clear draft button so staff doesn't wipe rollback data
        document.querySelectorAll('button[onclick*="clearDocketFormDraft"], button[onclick*="clearDocketFormDraftAndReset"]').forEach(btn => {
            btn.style.display = 'none';
        });

        // 2. Populate values from payload into form fields thoroughly across all sections
        populateFieldValues(payload);

        // 3. Lock all inputs except the ones that match flagged corrections
        const allInputs = form.querySelectorAll('input:not([type="hidden"]):not([name="_token"]):not(#rollbackSubmissionIdInput), textarea, select');
        
        allInputs.forEach(el => {
            const isTarget = isInputMatchingCorrections(el, corrections);
            
            if (isTarget.matched) {
                // UNLOCKED FIELD: Allowed to edit!
                el.readOnly = false;
                el.disabled = false;
                el.removeAttribute('readonly');
                el.removeAttribute('disabled');
                el.style.pointerEvents = 'auto';
                el.classList.remove('bg-slate-100', 'text-slate-600', 'cursor-not-allowed', 'border-slate-200');
                el.classList.add('border-amber-400', 'bg-amber-50/20', 'ring-2', 'ring-amber-400/40');
                
                if (el.parentElement) el.parentElement.style.pointerEvents = 'auto';
                if (el.closest('label')) el.closest('label').style.pointerEvents = 'auto';
                if (el.closest('#childrenInputContainer')) el.closest('#childrenInputContainer').style.pointerEvents = 'auto';
                if (el.closest('#relationshipDetailsBox')) el.closest('#relationshipDetailsBox').style.pointerEvents = 'auto';
                if (el.closest('#spouseFieldsCard')) el.closest('#spouseFieldsCard').style.pointerEvents = 'auto';
                if (el.closest('#partnerFieldsCard')) el.closest('#partnerFieldsCard').style.pointerEvents = 'auto';

                // Add visual badge above or next to the field if not added
                if (!el.parentElement.querySelector('.rollback-field-pill')) {
                    const badge = document.createElement('div');
                    badge.className = 'rollback-field-pill inline-flex items-center gap-1 px-2 py-0.5 rounded bg-green-100 text-green-950 border-none font-mono-code font-bold text-[10px] mt-1';
                    badge.innerHTML = `<svg class="w-3 h-3 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg><span>FIX: ${escapeHtml(isTarget.note || 'Correction required')}</span>`;
                    el.parentElement.appendChild(badge);
                }
            } else {
                // LOCKED FIELD: Read-only!
                if (el.tagName === 'SELECT' || el.type === 'checkbox' || el.type === 'radio' || el.type === 'file') {
                    el.disabled = true;
                    el.setAttribute('disabled', 'disabled');
                    el.style.pointerEvents = 'none';
                    el.classList.add('bg-slate-100', 'text-slate-500', 'cursor-not-allowed', 'opacity-70');
                } else if (el.tagName === 'TEXTAREA') {
                    el.readOnly = true;
                    el.setAttribute('readonly', 'readonly');
                    el.classList.add('bg-slate-100', 'text-slate-600', 'cursor-not-allowed', 'border-slate-200');
                    el.style.userSelect = 'text';
                } else {
                    el.readOnly = true;
                    el.setAttribute('readonly', 'readonly');
                    el.style.pointerEvents = 'none';
                    el.classList.add('bg-slate-100', 'text-slate-600', 'cursor-not-allowed', 'border-slate-200');
                }
                el.title = "Readonly: Docket entry locked by Unit Head.";
            }
        });

        // 4. Handle custom dropdown triggers (buttons)
        const customTriggers = [
            { id: 'genderDropdownTrigger', inputId: 'newArrestedGender' },
            { id: 'nationalityDropdownTrigger', inputId: 'newArrestedNationality' },
            { id: 'maritalDropdownTrigger', inputId: 'newArrestedMarital' },
            { id: 'operationTypeDropdownTrigger', inputId: 'operationTypeSelect' },
            { id: 'priorityDropdownTrigger', inputId: 'casePrioritySelect' },
            { id: 'caseEntryTypeDropdownTrigger', inputId: 'caseEntryTypeSelect' }
        ];

        customTriggers.forEach(item => {
            const btn = document.getElementById(item.id);
            const hiddenEl = document.getElementById(item.inputId);
            if (!btn || !hiddenEl) return;

            const isTarget = isInputMatchingCorrections(hiddenEl, corrections);
            if (isTarget.matched) {
                btn.style.pointerEvents = 'auto';
                btn.classList.add('border-amber-400', 'ring-2', 'ring-amber-400/40');
            } else {
                btn.style.pointerEvents = 'none';
                btn.classList.add('bg-slate-100', 'cursor-not-allowed', 'opacity-80');
            }
        });

        // 4b. Explicit handling for Marital/Spouse and Children sections
        const isMaritalFlagged = corrections.some(c => getCorrectionFieldKey(c.field) === 'marital');
        const isChildrenFlagged = corrections.some(c => getCorrectionFieldKey(c.field) === 'children');

        const unlockSpousePartnerInputs = () => {
            const rBox = document.getElementById('relationshipDetailsBox');
            if (rBox) {
                rBox.style.pointerEvents = 'auto';
                rBox.querySelectorAll('input, textarea, select').forEach(inp => {
                    inp.readOnly = false;
                    inp.disabled = false;
                    inp.removeAttribute('readonly');
                    inp.removeAttribute('disabled');
                    inp.style.pointerEvents = 'auto';
                    inp.classList.remove('bg-slate-100', 'text-slate-600', 'cursor-not-allowed', 'border-slate-200', 'opacity-70');
                    inp.classList.add('border-amber-400', 'bg-amber-50/20');
                });
            }
            const sCard = document.getElementById('spouseFieldsCard');
            if (sCard) sCard.style.pointerEvents = 'auto';
            const pCard = document.getElementById('partnerFieldsCard');
            if (pCard) pCard.style.pointerEvents = 'auto';
        };

        const unlockChildrenInputs = () => {
            const chk = document.getElementById('hasChildrenCheckbox');
            if (chk) {
                chk.disabled = false;
                chk.removeAttribute('disabled');
                chk.style.pointerEvents = 'auto';
                if (chk.parentElement) chk.parentElement.style.pointerEvents = 'auto';
                if (chk.closest('label')) chk.closest('label').style.pointerEvents = 'auto';
            }
            const childContainer = document.getElementById('childrenInputContainer');
            if (childContainer) childContainer.style.pointerEvents = 'auto';
            const childInput = document.getElementById('newArrestedChildren');
            if (childInput) {
                childInput.readOnly = false;
                childInput.disabled = false;
                childInput.removeAttribute('readonly');
                childInput.removeAttribute('disabled');
                childInput.style.pointerEvents = 'auto';
                childInput.classList.remove('bg-slate-100', 'text-slate-600', 'cursor-not-allowed', 'border-slate-200');
                childInput.classList.add('border-amber-400', 'bg-amber-50/20');
            }
        };

        if (isMaritalFlagged) {
            const mTrigger = document.getElementById('maritalDropdownTrigger');
            if (mTrigger) {
                mTrigger.style.pointerEvents = 'auto';
                mTrigger.classList.remove('cursor-not-allowed', 'opacity-80', 'bg-slate-100');
                mTrigger.classList.add('border-amber-400', 'ring-2', 'ring-amber-400/40');
            }
            unlockSpousePartnerInputs();
        }

        if (isChildrenFlagged) {
            unlockChildrenInputs();
        }

        // Dynamic wrap of handleMaritalChange and toggleChildrenInput so dynamically revealed fields stay unlocked
        if (typeof window.handleMaritalChange === 'function' && !window.handleMaritalChange._wrappedForRollback) {
            const origMaritalChange = window.handleMaritalChange;
            window.handleMaritalChange = function(val) {
                origMaritalChange(val);
                if (isMaritalFlagged) {
                    unlockSpousePartnerInputs();
                }
            };
            window.handleMaritalChange._wrappedForRollback = true;
        }

        if (typeof window.toggleChildrenInput === 'function' && !window.toggleChildrenInput._wrappedForRollback) {
            const origToggleChildren = window.toggleChildrenInput;
            window.toggleChildrenInput = function() {
                origToggleChildren();
                if (isChildrenFlagged) {
                    unlockChildrenInputs();
                }
            };
            window.toggleChildrenInput._wrappedForRollback = true;
        }

        // 5. Handle Mugshot photo cards locking
        window.triggerMugshotUpload = function(angleKey) {
            const input = document.getElementById(`file-${angleKey}`);
            if (input) {
                input.disabled = false;
                input.removeAttribute('disabled');
                input.style.pointerEvents = 'auto';
                input.click();
            }
        };

        const isMugshotFlagged = corrections.some(c => {
            const k = getCorrectionFieldKey(c.field);
            return k === 'mugshot' || (c.field && c.field.toLowerCase().includes('mugshot'));
        });

        if (isMugshotFlagged) {
            // UNLOCK & HIGHLIGHT MUGSHOT SECTION
            document.querySelectorAll('[id^="dropzone-"]').forEach(dz => {
                dz.style.pointerEvents = 'auto';
                dz.classList.remove('cursor-not-allowed', 'opacity-80', 'opacity-70');
                dz.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-400/40');

                // Allow clicking dropzone background/overlay to trigger upload
                const angleKey = dz.id.replace('dropzone-', '');
                const actions = dz.querySelector('[id^="actions-"]');
                if (actions) {
                    actions.style.pointerEvents = 'auto';
                    actions.onclick = function(e) {
                        if (!e.target.closest('button')) {
                            window.triggerMugshotUpload(angleKey);
                        }
                    };
                    // Inject a dedicated "Change Photo" button if not already present
                    if (!actions.querySelector('.btn-change-mugshot')) {
                        const changeBtn = document.createElement('button');
                        changeBtn.type = 'button';
                        changeBtn.className = 'btn-change-mugshot px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none';
                        changeBtn.innerHTML = `
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <span>Change Photo</span>
                        `;
                        changeBtn.onclick = function(ev) {
                            ev.preventDefault();
                            ev.stopPropagation();
                            window.triggerMugshotUpload(angleKey);
                        };
                        const clearBtn = actions.querySelector('button[onclick*="clearArrestMugshotPhoto"]');
                        if (clearBtn) {
                            actions.insertBefore(changeBtn, clearBtn);
                        } else {
                            actions.appendChild(changeBtn);
                        }
                    }
                }
            });

            // Ensure all mugshot file inputs are unlocked and enabled
            document.querySelectorAll('input[type="file"]').forEach(inp => {
                const id = (inp.id || '').toLowerCase();
                if (id.includes('front') || id.includes('profile') || id.includes('mugshot')) {
                    inp.disabled = false;
                    inp.removeAttribute('disabled');
                    inp.style.pointerEvents = 'auto';
                }
            });

            // Ensure all mugshot url inputs are enabled
            document.querySelectorAll('input[id^="url-"]').forEach(inp => {
                const id = (inp.id || '').toLowerCase();
                if (id.includes('front') || id.includes('profile') || id.includes('mugshot')) {
                    inp.disabled = false;
                    inp.readOnly = false;
                    inp.removeAttribute('disabled');
                    inp.removeAttribute('readonly');
                    inp.style.pointerEvents = 'auto';
                    inp.classList.remove('bg-slate-100', 'cursor-not-allowed');
                }
            });

            // Unhide upload buttons & clear buttons
            document.querySelectorAll('button[onclick*="triggerMugshotUpload"], button[onclick*="clearArrestMugshotPhoto"]').forEach(el => {
                el.style.display = '';
                el.style.pointerEvents = 'auto';
                el.disabled = false;
                el.removeAttribute('disabled');
            });

            // Also enable the "Add Mugshot Row" button if syndicate mode
            const addMugBtn = document.getElementById('addMugshotRowBtn');
            if (addMugBtn) {
                addMugBtn.style.pointerEvents = 'auto';
            }

            const mugshotSection = document.getElementById('mugshotsContainer') || document.querySelector('[id*="mugshot"]');
            if (mugshotSection && !mugshotSection.querySelector('.rollback-mugshot-pill')) {
                const pill = document.createElement('div');
                pill.className = 'rollback-mugshot-pill mb-3 inline-flex items-center gap-1.5 px-3 py-1 bg-amber-500 text-white font-mono-code font-bold text-xs rounded-xl shadow-sm';
                pill.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg><span>FIX REQUIRED: Unit Head requested mugshot correction &mdash; Click any photo box or "Change Photo" to upload replacement</span>`;
                mugshotSection.prepend(pill);
            }
        } else {
            // LOCK MUGSHOTS
            document.querySelectorAll('[id^="dropzone-"]').forEach(dz => {
                dz.style.pointerEvents = 'none';
                dz.classList.add('cursor-not-allowed', 'opacity-80');
            });
            document.querySelectorAll('button[onclick*="triggerMugshotUpload"], button[onclick*="clearArrestMugshotPhoto"], input[id^="url-"]').forEach(el => {
                el.style.display = 'none';
            });
            document.querySelectorAll('input[type="file"]').forEach(inp => {
                const id = (inp.id || '').toLowerCase();
                if (id.includes('front') || id.includes('profile') || id.includes('mugshot')) {
                    inp.disabled = true;
                    inp.setAttribute('disabled', 'disabled');
                    inp.style.pointerEvents = 'none';
                }
            });
        }

        // 6. Handle Evidence Photos Dropzone & Delete Buttons Locking
        const isPhotosFlagged = corrections.some(c => getCorrectionFieldKey(c.field) === 'photos');
        if (!isPhotosFlagged) {
            document.querySelectorAll('#seizurePhotoDropzone, .seizure-photo-dropzone').forEach(dz => {
                dz.style.pointerEvents = 'none';
                dz.classList.add('cursor-not-allowed', 'opacity-70');
                dz.onclick = null;
            });
            document.querySelectorAll('.remove-photo-btn, button[onclick*="triggerSeizurePhotoUpload"]').forEach(el => {
                el.style.display = 'none';
            });
        }

        // 7. Hide all search dropdown list panels to prevent uninvited popups
        document.querySelectorAll('#exhibitListPanel, #deviceListPanel, #drugListPanel, #weaponListPanel, #currencyListPanel, #ammoListPanel, #pkgListPanel, #unitListPanel, #vehTypeListPanel, #incidentLocationListPanel, #facilityListPanel').forEach(p => {
            p.classList.add('hidden');
        });

        // 8. Hide add/remove block and row buttons unless explicitly flagged
        const isSeizureAddRemoveFlagged = corrections.some(c => {
            const k = getCorrectionFieldKey(c.field);
            return k === 'category' || k === 'commodity_name' || k === 'photos' || k === 'seizures';
        });

        if (!isSeizureAddRemoveFlagged) {
            document.querySelectorAll('button[onclick*="addSeizureBlock"], button[onclick*="removeSeizureBlock"], button[onclick*="addSeizureOfficerRow"], button[onclick*="removeSeizureOfficerRow"], button[onclick*="addSuspectPhoneRow"], button[onclick*="addSuspectDetailsRow"], button[onclick*="removeSuspectCard"]').forEach(btn => {
                btn.style.display = 'none';
            });
        }

        // 8b. Handle Section 5 (Forensic Examination & Lab Analysis) and GSA Report Locking
        const isSection5Flagged = corrections.some(c => {
            const k = getCorrectionFieldKey(c.field);
            return k === 'lab_examination' || k === 'presumptive' || k === 'confirmatory' || k === 'sample_weight' ||
                   k === 'screening_method' || k === 'purpose_of_examination' || k === 'preliminary_exam_procedure' ||
                   k === 'test_methods' || k === 'specifications' || k === 'confirmatory_remark' || k === 'disposition' ||
                   k === 'lab_analyst' || k === 'lab_number' || k === 'lab_exam_reference';
        });

        const isGsaFlagged = corrections.some(c => {
            const k = getCorrectionFieldKey(c.field);
            return k === 'gsa' || k === 'gsa_lab_number' || k === 'gsa_case_reference' || k === 'gsa_status' || k === 'gsa_exhibit_type';
        });

        const isSection4Flagged = corrections.some(c => {
            const k = getCorrectionFieldKey(c.field);
            return k === 'seizures' || k === 'commodity_name' || k === 'category' || k === 'weight' || k === 'quantity' ||
                   k === 'unit' || k === 'value' || k === 'concealment' || k === 'seizure_reason' || k === 'remark' ||
                   k === 'photos' || k === 'dev_model' || k === 'dev_serial' || k === 'dev_imei' || k === 'dev_mac' ||
                   k === 'dev_storage' || k === 'dev_colour' || k === 'dev_marks';
        });

        if (!isSection5Flagged && !isGsaFlagged) {
            // Lock Module Switcher buttons in preview mode
            ['toggleGsaReportBtn', 'toggleNlrReportBtn', 'resetLabModulesBtn'].forEach(bId => {
                const btn = document.getElementById(bId);
                if (btn) {
                    btn.style.pointerEvents = 'none';
                    btn.disabled = true;
                    btn.setAttribute('disabled', 'disabled');
                    btn.classList.add('opacity-60', 'cursor-not-allowed');
                    btn.title = "Locked: Lab Module Switcher is locked in read-only preview mode during rollback.";
                }
            });

            // Hide dynamic row and test buttons in Section 5 & GSA
            document.querySelectorAll('button[onclick*="addScreeningMethodRow"], button[onclick*="removeScreeningMethodRow"], button[onclick*="addGsaExhibitTypeInput"], #gsaClearAllBtn, button[onclick*="addPresumptiveTestRowForExhibit"], button[onclick*="removePresumptiveTestRowForExhibit"], button[onclick*="addDetectedSubstanceRowForExhibit"], button[onclick*="removeDetectedSubstanceRowForExhibit"]').forEach(btn => {
                btn.style.display = 'none';
            });

            // Lock GSA Dropzone
            const gsaDz = document.getElementById('gsaDropZone');
            if (gsaDz) {
                gsaDz.style.pointerEvents = 'none';
                gsaDz.classList.add('cursor-not-allowed', 'opacity-80');
                const fullPrompt = document.getElementById('gsaFullPrompt');
                const addMore = document.getElementById('gsaAddMoreStrip');
                if (fullPrompt) fullPrompt.style.pointerEvents = 'none';
                if (addMore) addMore.style.display = 'none';
            }

            // Add Locked Preview Banner in Section 5 if not already present
            const nlrSec = document.getElementById('labAnalysisExclusiveSections');
            if (nlrSec && !nlrSec.querySelector('.rollback-section5-preview-pill')) {
                const banner = document.createElement('div');
                banner.className = 'rollback-section5-preview-pill p-3 mb-4 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-xs flex items-center justify-between font-mono-code font-bold';
                banner.innerHTML = `<span class="flex items-center gap-2"><svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg><span>PREVIEW ONLY: Forensic Examination & Laboratory Analysis locked by Unit Head</span></span><span class="px-2 py-0.5 rounded bg-slate-200 text-slate-800 text-[10px]">READ-ONLY</span>`;
                const formCont = document.getElementById('section5FormContainer') || nlrSec;
                formCont.prepend(banner);
            }
        }

        if (!isSection4Flagged) {
            // Add Locked Preview Banner in Section 4 if not already present
            const seizureCont = document.getElementById('seizuresDetailsContainer');
            if (seizureCont && !seizureCont.querySelector('.rollback-section4-preview-pill')) {
                const banner = document.createElement('div');
                banner.className = 'rollback-section4-preview-pill p-3 mb-4 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-xs flex items-center justify-between font-mono-code font-bold';
                banner.innerHTML = `<span class="flex items-center gap-2"><svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg><span>PREVIEW ONLY: Seized Commodities & Exhibits locked by Unit Head</span></span><span class="px-2 py-0.5 rounded bg-slate-200 text-slate-800 text-[10px]">READ-ONLY</span>`;
                seizureCont.prepend(banner);
            }
        }

        // 9. Attach capture-phase event listeners to prevent editing locked fields
        if (!form._rollbackListenersAttached) {
            form._rollbackListenersAttached = true;

            form.addEventListener('keydown', function(e) {
                const target = e.target;
                if (!target) return;
                const isLocked = target.readOnly || target.disabled || target.getAttribute('readonly') !== null;
                if (isLocked && !isInputMatchingCorrections(target, window.ROLLBACK_CORRECTIONS).matched) {
                    if (['Tab', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'PageUp', 'PageDown'].includes(e.key)) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);

            form.addEventListener('paste', function(e) {
                const target = e.target;
                if (!target) return;
                const isLocked = target.readOnly || target.disabled || target.getAttribute('readonly') !== null;
                if (isLocked && !isInputMatchingCorrections(target, window.ROLLBACK_CORRECTIONS).matched) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
        }

        // 10. Update Submit button text and styling across all submit buttons
        const submitButtons = [];
        const btn1 = document.getElementById('submitSection1To4DocketBtn');
        const btn2 = document.getElementById('submitArrestDocketBtn');
        if (btn1) submitButtons.push(btn1);
        if (btn2 && !submitButtons.includes(btn2)) submitButtons.push(btn2);

        document.querySelectorAll('button').forEach(btn => {
            const txt = (btn.textContent || '').trim();
            if ((txt.includes('Save Case') || txt.includes('Save Exhibit') || txt.includes('Confirm & Resubmit') || txt.includes('Send for Review')) && !submitButtons.includes(btn)) {
                submitButtons.push(btn);
            }
        });

        submitButtons.forEach(btn => {
            btn.classList.remove('bg-amber-600', 'hover:bg-amber-700', 'shadow-amber-600/30');
            btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700', 'shadow-md', 'shadow-emerald-600/30');
            btn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <span>Send for Review and Approval</span>
            `;
            btn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                handleRollbackResubmit();
            };
        });

        // Ensure processNewArrested delegates to rollback resubmit when in rollback mode
        window.processNewArrested = function() {
            handleRollbackResubmit();
        };

        form.onsubmit = function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleRollbackResubmit();
        };
    }

    function handleRollbackResubmit() {
        const form = document.getElementById('arrestDocketForm');
        if (!form) return;

        // Build complete payload starting from existing payload
        const payload = Object.assign({}, window.ROLLBACK_PAYLOAD || {});

        // Extract fresh values from all DOM inputs
        const getVal = (id) => {
            const el = document.getElementById(id);
            return el ? el.value : '';
        };

        const suspectName = getVal('newArrestedName') || payload.suspect_name || payload.full_name || '';
        const alias = getVal('newArrestedAlias') || payload.alias || payload.suspect_alias || '';
        const idCard = getVal('newArrestedID') || payload.id_card || payload.ghana_card || '';
        const gender = getVal('newArrestedGender') || payload.gender || '';
        const dob = getVal('newArrestedDOB') || payload.dob || '';
        const age = getVal('newArrestedAge') || payload.age || '';
        const height = getVal('newArrestedHeight') || payload.height || '';
        const eyeColor = getVal('newArrestedEyeColor') || payload.eye_color || '';
        const nationality = getVal('nationalitySelect') || getVal('newArrestedNationality') || payload.nationality || 'Ghanaian';
        const pob = getVal('newArrestedPOB') || payload.pob || '';
        const phone = getVal('newArrestedPhone') || payload.phone || '';
        const address = getVal('newArrestedAddress') || payload.address || '';
        const marital = getVal('newArrestedMarital') || payload.marital_status || '';
        const children = getVal('newArrestedChildren') || payload.children_count || '0';
        const hasKidsBox = document.getElementById('hasChildrenCheckbox');
        const hasKids = hasKidsBox ? (hasKidsBox.checked ? 1 : 0) : ((children && parseInt(children) > 0) ? 1 : (payload.has_children ? 1 : 0));
        const numKidsInt = parseInt(children) || 0;
        const dependentsText = hasKids ? ((numKidsInt > 0 ? numKidsInt : 1) + ((numKidsInt === 1) ? ' Kid' : ' Kids')) : 'No Children';
        const spouseName = getVal('newArrestedSpouseName') || payload.spouse_name || '';
        const spousePhone = getVal('newArrestedSpousePhone') || payload.spouse_phone || '';
        const spouseInfo = getVal('newArrestedSpouseInfo') || payload.spouse_info || '';
        const partnerName = getVal('newArrestedPartnerName') || payload.partner_name || '';
        const partnerPhone = getVal('newArrestedPartnerPhone') || payload.partner_phone || '';
        const partnerInfo = getVal('newArrestedPartnerInfo') || payload.partner_info || '';
        const offense = getVal('newArrestedOffense') || payload.offense_description || payload.offense || '';
        const location = getVal('newArrestedIncidentLocation') || getVal('newArrestedRaidLocation') || payload.location || payload.raid_location || '';
        const facility = getVal('newArrestedHoldingFacility') || payload.holding_facility || '';
        const arrestDate = getVal('newArrestedDate') || payload.arrest_date || '';
        const arrestTime = getVal('newArrestedTime') || payload.arrest_time || '';
        const title = getVal('caseTitleInput') || payload.title || 'Staff Case Submission';
        const priority = getVal('casePrioritySelect') || payload.priority || 'HIGH';

        // Extract Seizures if blocks exist
        const seizureBlocks = document.querySelectorAll('.seizure-block');
        let seizures = [];
        if (seizureBlocks.length > 0) {
            seizureBlocks.forEach((block, idx) => {
                const baseS = (Array.isArray(payload.seizures) && payload.seizures[idx]) || {};
                const s = Object.assign({}, baseS);
                const cat = block.querySelector('.exhibit-category-select')?.value;
                if (cat) s.exhibit_category = cat;
                const dName = block.querySelector('.drug-name-select')?.value;
                if (dName) s.seizure_name_drug = dName;
                const wName = block.querySelector('.weapon-type-select')?.value;
                if (wName) s.seizure_name_weapon = wName;
                const devName = block.querySelector('.device-type-select')?.value;
                if (devName) s.seizure_name_device = devName;
                const cName = block.querySelector('.commodity-name-input')?.value;
                if (cName) s.commodity_name = cName;
                const wt = block.querySelector('.seizure-weight-input')?.value;
                if (wt) s.seizure_weight = wt;
                const qty = block.querySelector('.seizure-quantity-input')?.value;
                if (qty) s.seizure_quantity = qty;
                const val = block.querySelector('.seizure-value-input')?.value;
                if (val) s.seizure_value = val;
                const loc = block.querySelector('.seizure-location-input')?.value;
                if (loc) s.seizure_location = loc;
                const off = block.querySelector('.seizure-officer-input')?.value;
                if (off) s.seizure_officer = off;
                const rem = block.querySelector('.seizure-remark-input, #newSeizureRemark')?.value;
                if (rem) s.exhibit_remark = rem;
                seizures.push(s);
            });
        } else if (Array.isArray(payload.seizures)) {
            seizures = payload.seizures;
        }

        const mugFront = getVal('hidden-front')
            || (document.getElementById('preview-front')?.src || '')
            || payload.mugshot_front
            || payload.docket_data?.mugshot_front
            || payload.docket_data?.suspects?.[0]?.mugshot_front
            || payload.suspects?.[0]?.mugshot_front
            || '';
        const mugLeft = getVal('hidden-left-profile')
            || (document.getElementById('preview-left-profile')?.src || '')
            || payload.mugshot_left
            || payload.mugshot_left_profile
            || payload.docket_data?.mugshot_left
            || payload.docket_data?.suspects?.[0]?.mugshot_left
            || payload.suspects?.[0]?.mugshot_left
            || '';
        const mugRight = getVal('hidden-right-profile')
            || (document.getElementById('preview-right-profile')?.src || '')
            || payload.mugshot_right
            || payload.mugshot_right_profile
            || payload.docket_data?.mugshot_right
            || payload.docket_data?.suspects?.[0]?.mugshot_right
            || payload.suspects?.[0]?.mugshot_right
            || '';

        const requestBody = {
            suspect_name: suspectName,
            full_name: suspectName,
            alias: alias,
            suspect_alias: alias,
            id_card: idCard,
            ghana_card: idCard,
            gender: gender,
            dob: dob,
            age: age,
            height: height,
            eye_color: eyeColor,
            nationality: nationality,
            pob: pob,
            phone: phone,
            address: address,
            marital_status: marital,
            has_children: hasKids,
            children_count: hasKids ? (numKidsInt > 0 ? numKidsInt : 1) : '0',
            dependents: dependentsText,
            spouse_name: spouseName,
            spouse_phone: spousePhone,
            spouse_info: spouseInfo,
            partner_name: partnerName,
            partner_phone: partnerPhone,
            partner_info: partnerInfo,
            offense_description: offense,
            offense: offense,
            location: location,
            raid_location: location,
            holding_facility: facility,
            arrest_date: arrestDate,
            arrest_time: arrestTime,
            title: title,
            priority: priority,
            mugshot_front: mugFront,
            mugshot_left: mugLeft,
            mugshot_right: mugRight,
            mugshots: {
                front: mugFront,
                left: mugLeft,
                right: mugRight
            },
            seizures: seizures
        };

        // Extract Section 5 Lab Examination & GSA details if present
        const labWorkflowMode = window.activeLabWorkflowMode || getVal('labWorkflowModeInput') || payload.lab_workflow_mode || '';
        const labExamRef = getVal('labExamReferenceInput') || payload.lab_exam_reference || payload.exam_reference || '';
        const labCaseRef = getVal('labCaseReferenceInput') || payload.lab_case_reference || payload.case_reference || '';
        const labExhNo = getVal('labExhibitNumberInput') || payload.lab_exhibit_number || payload.lab_exhibit_no || '';
        const labExhDesc = getVal('labExhibitDescriptionInput') || payload.lab_exhibit_description || payload.exhibit_description || '';
        const labNumber = getVal('labNumberInput') || payload.laboratory_number || payload.lab_number || '';
        const labAnalyst = getVal('labAnalystInput') || payload.lab_analyst || '';
        const labDateRecv = getVal('labDateReceivedInput') || payload.lab_date_received || '';
        const labDateStart = getVal('labDateStartedInput') || payload.lab_date_started || '';
        const labDateComp = getVal('labDateCompletedInput') || payload.lab_date_completed || '';
        const labPurpose = getVal('labPurposeOfExaminationInput') || payload.purpose_of_examination || '';
        const labPrelim = getVal('labPreliminaryExamProcedureInput') || payload.preliminary_exam_procedure || '';
        const labTestMethods = getVal('confirmatoryTestMethods') || payload.confirmatory_test_methods || payload.test_methods || '';
        const labSpecs = getVal('confirmatorySpecifications') || payload.confirmatory_specifications || payload.specifications || '';
        const labRemark = getVal('confirmatoryRemark') || payload.confirmatory_remark || payload.remark || '';
        const labDisposition = getVal('confirmatoryDispositionOfExhibit') || payload.disposition_of_exhibit || '';

        // Screening methods
        let screeningMethods = [];
        document.querySelectorAll('.screening-method-input').forEach(inp => {
            if (inp.value && inp.value.trim() !== '') screeningMethods.push(inp.value.trim());
        });
        if (screeningMethods.length === 0 && Array.isArray(payload.screening_method)) {
            screeningMethods = payload.screening_method;
        }

        // GSA fields
        const gsaLabNum = getVal('gsaLabNumberInput') || payload.gsa_lab_number || '';
        const gsaCaseRef = getVal('gsaCaseReferenceInput') || payload.gsa_case_reference || '';
        const gsaStatus = getVal('gsaStatusInput') || payload.gsa_status || '';
        let gsaExhibitTypes = [];
        document.querySelectorAll('.gsa-exhibit-type-input').forEach(inp => {
            if (inp.value && inp.value.trim() !== '') gsaExhibitTypes.push(inp.value.trim());
        });
        if (gsaExhibitTypes.length === 0 && Array.isArray(payload.exhibit_type)) {
            gsaExhibitTypes = payload.exhibit_type;
        }

        // Per-exhibit lab examination data
        let labExhibitNos = [];
        document.querySelectorAll('.card-exhibit-no-input').forEach(inp => {
            labExhibitNos.push(inp.value || '');
        });
        if (labExhibitNos.length === 0 && Array.isArray(payload.lab_exhibit_nos)) {
            labExhibitNos = payload.lab_exhibit_nos;
        }

        // Merge Section 5 and GSA into requestBody
        if (labWorkflowMode) requestBody.lab_workflow_mode = labWorkflowMode;
        if (labExamRef) {
            requestBody.lab_exam_reference = labExamRef;
            requestBody.exam_reference = labExamRef;
        }
        if (labCaseRef) requestBody.lab_case_reference = labCaseRef;
        if (labExhNo) {
            requestBody.lab_exhibit_number = labExhNo;
            requestBody.lab_exhibit_no = labExhNo;
        }
        if (labExhDesc) {
            requestBody.lab_exhibit_description = labExhDesc;
            requestBody.exhibit_description = labExhDesc;
        }
        if (labNumber) requestBody.laboratory_number = labNumber;
        if (labAnalyst) requestBody.lab_analyst = labAnalyst;
        if (labDateRecv) requestBody.lab_date_received = labDateRecv;
        if (labDateStart) requestBody.lab_date_started = labDateStart;
        if (labDateComp) requestBody.lab_date_completed = labDateComp;
        if (labPurpose) requestBody.purpose_of_examination = labPurpose;
        if (labPrelim) requestBody.preliminary_exam_procedure = labPrelim;
        if (screeningMethods.length > 0) requestBody.screening_method = screeningMethods;
        if (labTestMethods) {
            requestBody.confirmatory_test_methods = labTestMethods;
            requestBody.test_methods = labTestMethods;
        }
        if (labSpecs) {
            requestBody.confirmatory_specifications = labSpecs;
            requestBody.specifications = labSpecs;
        }
        if (labRemark) {
            requestBody.confirmatory_remark = labRemark;
            requestBody.remark = labRemark;
        }
        if (labDisposition) requestBody.disposition_of_exhibit = labDisposition;
        if (gsaLabNum) requestBody.gsa_lab_number = gsaLabNum;
        if (gsaCaseRef) requestBody.gsa_case_reference = gsaCaseRef;
        if (gsaStatus) requestBody.gsa_status = gsaStatus;
        if (gsaExhibitTypes.length > 0) requestBody.exhibit_type = gsaExhibitTypes;
        if (labExhibitNos.length > 0) requestBody.lab_exhibit_nos = labExhibitNos;

        // Preserve and build complete lab_examination object
        const existingLabExam = (payload.docket_data && payload.docket_data.lab_examination) || payload.lab_examination || {};
        requestBody.lab_examination = Object.assign({}, existingLabExam, {
            exam_reference: labExamRef || existingLabExam.exam_reference,
            case_reference: labCaseRef || existingLabExam.case_reference,
            laboratory_number: labNumber || existingLabExam.laboratory_number,
            lab_analyst: labAnalyst || existingLabExam.lab_analyst,
            lab_date_received: labDateRecv || existingLabExam.lab_date_received,
            lab_date_started: labDateStart || existingLabExam.lab_date_started,
            lab_date_completed: labDateComp || existingLabExam.lab_date_completed,
            purpose_of_examination: labPurpose || existingLabExam.purpose_of_examination,
            preliminary_exam_procedure: labPrelim || existingLabExam.preliminary_exam_procedure,
            screening_method: screeningMethods.length > 0 ? screeningMethods : existingLabExam.screening_method,
            test_methods: labTestMethods || existingLabExam.test_methods,
            specifications: labSpecs || existingLabExam.specifications,
            remark: labRemark || existingLabExam.remark,
            disposition_of_exhibit: labDisposition || existingLabExam.disposition_of_exhibit,
            gsa_lab_number: gsaLabNum || existingLabExam.gsa_lab_number,
            gsa_case_reference: gsaCaseRef || existingLabExam.gsa_case_reference,
            gsa_status: gsaStatus || existingLabExam.gsa_status
        });

        for (let sIdx = 2; sIdx <= 10; sIdx++) {
            const hFront = document.getElementById(`hidden-front-${sIdx}`)?.value || (document.getElementById(`preview-front-${sIdx}`)?.src || '');
            const hLeft = document.getElementById(`hidden-left-profile-${sIdx}`)?.value || (document.getElementById(`preview-left-profile-${sIdx}`)?.src || '');
            const hRight = document.getElementById(`hidden-right-profile-${sIdx}`)?.value || (document.getElementById(`preview-right-profile-${sIdx}`)?.src || '');
            if (hFront) requestBody[`mugshot_front_${sIdx}`] = hFront;
            if (hLeft) requestBody[`mugshot_left_${sIdx}`] = hLeft;
            if (hRight) requestBody[`mugshot_right_${sIdx}`] = hRight;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Show submitting state on all buttons
        const submitButtons = [];
        const btn1 = document.getElementById('submitSection1To4DocketBtn');
        const btn2 = document.getElementById('submitArrestDocketBtn');
        if (btn1) submitButtons.push(btn1);
        if (btn2 && !submitButtons.includes(btn2)) submitButtons.push(btn2);

        submitButtons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Sending for Review and Approval...</span>
            `;
        });

        fetch(window.ROLLBACK_RESUBMIT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestBody)
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (res.ok && (data.success || data.redirect)) {
                if (typeof showSuccessPopover === 'function') {
                    showSuccessPopover('Corrected Docket Sent for Approval');
                } else if (typeof window.showFlashToast === 'function') {
                    window.showFlashToast(data.message || 'Case updated and sent for review and approval.', 'success', 'Sent for Review');
                } else {
                    alert(data.message || 'Case has been updated and sent to your Unit Head for review and approval.');
                }
                setTimeout(() => {
                    window.location.href = data.redirect || window.location.href;
                }, 800);
            } else {
                submitButtons.forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span>Send for Review and Approval</span>
                    `;
                });
                alert(data.message || 'Failed to submit corrections. Please check your entries.');
            }
        })
        .catch(err => {
            submitButtons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span>Send for Review and Approval</span>
                `;
            });
            alert('An error occurred while submitting corrections. Please try again.');
        });
    }

    function escapeHtml(str) {
        return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Run on DOM ready and after any dynamic render / initialization timeouts
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(applyRollbackDocketMode, 50);
            setTimeout(applyRollbackDocketMode, 200);
            setTimeout(applyRollbackDocketMode, 500);
        });
    } else {
        setTimeout(applyRollbackDocketMode, 50);
        setTimeout(applyRollbackDocketMode, 200);
        setTimeout(applyRollbackDocketMode, 500);
    }
})();
</script>
@endif
