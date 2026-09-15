<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ForensicCase;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cases', 'laboratory_number')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->string('laboratory_number')->nullable()->after('case_number');
            });
        }

        // Backfill existing records
        $cases = ForensicCase::all();
        foreach ($cases as $case) {
            $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            
            $existingLabNum = $case->laboratory_number ?? ($docketData['lab_examination']['laboratory_number'] ?? null);
            if (!$existingLabNum) {
                $rand = rand(10, 99);
                $month = date('m');
                $year = date('Y');
                $newLabNum = "Lab No.{$rand}/FS{$month}/{$year}";

                $case->laboratory_number = $newLabNum;
                if (!isset($docketData['lab_examination'])) {
                    $docketData['lab_examination'] = [];
                }
                $docketData['lab_examination']['laboratory_number'] = $newLabNum;
                $case->docket_data = $docketData;
                $case->save();
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cases', 'laboratory_number')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->dropColumn('laboratory_number');
            });
        }
    }
};
