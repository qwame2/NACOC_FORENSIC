<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ForensicCase;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases', 'laboratory_analytical_findings')) {
                $table->text('laboratory_analytical_findings')->nullable();
            }
        });

        // Migrate findings from docket_data->lab_examination or unit_notes for existing records
        $cases = ForensicCase::all();
        foreach ($cases as $case) {
            $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            $labExam = $docketData['lab_examination'] ?? [];
            $unitNotes = $docketData['unit_notes'] ?? [];

            $findings = $labExam['findings'] ?? ($unitNotes['Lab Analysis']['notes'] ?? null);

            if (!empty($findings)) {
                $case->laboratory_analytical_findings = $findings;
                $case->save();
            }
        }
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (Schema::hasColumn('cases', 'laboratory_analytical_findings')) {
                $table->dropColumn('laboratory_analytical_findings');
            }
        });
    }
};
