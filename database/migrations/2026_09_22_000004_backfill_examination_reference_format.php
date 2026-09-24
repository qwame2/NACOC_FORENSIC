<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\LaboratoryExaminationResult;
use App\Models\AssociateLabExamination;

return new class extends Migration
{
    public function up(): void
    {
        $isValidExamRef = function($val) {
            if (empty($val) || !is_string($val)) return false;
            return (bool)preg_match('/^GSA-FM-T\d+-F$/', trim($val));
        };

        $generateNewExamRef = function() {
            $rand = rand(10, 99);
            return "GSA-FM-T{$rand}-F";
        };

        $labs = LaboratoryExaminationResult::all();
        foreach ($labs as $l) {
            if (!$isValidExamRef($l->exam_reference)) {
                $l->exam_reference = $generateNewExamRef();
                $l->save();
            }
        }

        $assocLabs = AssociateLabExamination::all();
        foreach ($assocLabs as $al) {
            if (!$isValidExamRef($al->exam_reference)) {
                $al->exam_reference = $generateNewExamRef();
                $al->save();
            }
        }
    }

    public function down(): void
    {
        // No down migration required
    }
};
