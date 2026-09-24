<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\AssociateLabExamination;

return new class extends Migration
{
    public function up(): void
    {
        $isValidLabNo = function($val) {
            if (empty($val) || !is_string($val)) return false;
            return (bool)preg_match('/^Lab No\. \d+\/FS\d{2}\/\d{4}$/', trim($val));
        };

        $generateNewLabNo = function($createdAt = null) {
            $rand = rand(10, 99);
            $m = $createdAt ? date('m', strtotime((string)$createdAt)) : date('m');
            $y = $createdAt ? date('Y', strtotime((string)$createdAt)) : date('Y');
            return "Lab No. {$rand}/FS{$m}/{$y}";
        };

        // 1. Backfill SingleSuspectCases
        $singles = SingleSuspectCase::all();
        foreach ($singles as $s) {
            if (!$isValidLabNo($s->laboratory_number)) {
                $newLabNo = $generateNewLabNo($s->created_at);
                $s->laboratory_number = $newLabNo;
                $s->save();
            } else {
                $newLabNo = $s->laboratory_number;
            }

            $labs = LaboratoryExaminationResult::where('single_suspect_case_id', $s->id)->get();
            foreach ($labs as $l) {
                if (!$isValidLabNo($l->laboratory_number)) {
                    $l->laboratory_number = $newLabNo;
                    $l->save();
                }
            }
        }

        // 2. Backfill MultipleSuspectCases
        $multis = MultipleSuspectCase::all();
        foreach ($multis as $m) {
            if (!$isValidLabNo($m->laboratory_number)) {
                $newLabNo = $generateNewLabNo($m->created_at);
                $m->laboratory_number = $newLabNo;
                $m->save();
            } else {
                $newLabNo = $m->laboratory_number;
            }

            $labs = LaboratoryExaminationResult::where('multiple_suspect_case_id', $m->id)->get();
            foreach ($labs as $l) {
                if (!$isValidLabNo($l->laboratory_number)) {
                    $l->laboratory_number = $newLabNo;
                    $l->save();
                }
            }

            $assocLabs = AssociateLabExamination::where('case_id', $m->id)->get();
            foreach ($assocLabs as $al) {
                if (!$isValidLabNo($al->laboratory_number)) {
                    $al->laboratory_number = $generateNewLabNo($al->created_at);
                    $al->save();
                }
            }
        }

        // 3. Orphaned LaboratoryExaminationResults
        $orphanLabs = LaboratoryExaminationResult::whereNull('single_suspect_case_id')
            ->whereNull('multiple_suspect_case_id')
            ->get();
        foreach ($orphanLabs as $ol) {
            if (!$isValidLabNo($ol->laboratory_number)) {
                $ol->laboratory_number = $generateNewLabNo($ol->created_at);
                $ol->save();
            }
        }
    }

    public function down(): void
    {
        // No down migration required
    }
};
