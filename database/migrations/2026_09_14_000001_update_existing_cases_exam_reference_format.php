<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\ForensicCase;

return new class extends Migration
{
    public function up(): void
    {
        $cases = ForensicCase::all();
        foreach ($cases as $case) {
            $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            
            $updated = false;
            if (!isset($docketData['lab_examination'])) {
                $docketData['lab_examination'] = [];
            }
            
            $currentRef = $docketData['lab_examination']['exam_reference'] ?? null;
            if (!$currentRef || str_starts_with($currentRef, 'EXAM-')) {
                $docketData['lab_examination']['exam_reference'] = 'GSA-FM-T' . rand(10, 99) . '-F';
                $updated = true;
            }

            if ($updated) {
                $case->docket_data = $docketData;
                $case->save();
            }
        }
    }

    public function down(): void
    {
        // No reverse required
    }
};
