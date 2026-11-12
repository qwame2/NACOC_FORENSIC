<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exhibits')) {
            return;
        }

        Schema::table('exhibits', function (Blueprint $table) {
            if (!Schema::hasColumn('exhibits', 'lab_exhibit_no')) {
                $table->string('lab_exhibit_no')->nullable()->after('exhibit_number');
            }
        });

        // Normalize any null, empty, numeric, or duplicate exhibit_number records before adding unique constraint
        $rows = DB::table('exhibits')->select('id', 'exhibit_number')->orderBy('id')->get();
        $maxSeq = 0;
        $usedNumbers = [];

        foreach ($rows as $row) {
            $val = is_string($row->exhibit_number) ? trim($row->exhibit_number) : (is_numeric($row->exhibit_number) ? (string) $row->exhibit_number : '');
            if ($val !== '' && preg_match('/^EXH-(\d+)$/i', $val, $m)) {
                $num = (int) $m[1];
                if ($num > $maxSeq && $num < 100000000) {
                    $maxSeq = $num;
                }
            }
        }

        $nextSeq = $maxSeq + 1;
        foreach ($rows as $row) {
            $val = is_string($row->exhibit_number) ? trim($row->exhibit_number) : (is_numeric($row->exhibit_number) ? (string) $row->exhibit_number : '');
            $needsNewId = ($val === '' || ctype_digit($val) || preg_match('/^EXH-\d{4}-\d{3}-NFC$/i', $val) || isset($usedNumbers[strtoupper($val)]));

            if ($needsNewId) {
                do {
                    $candidate = sprintf('EXH-%05d', $nextSeq);
                    $nextSeq++;
                } while (isset($usedNumbers[strtoupper($candidate)]) || DB::table('exhibits')->where('exhibit_number', $candidate)->where('id', '!=', $row->id)->exists());

                DB::table('exhibits')->where('id', $row->id)->update(['exhibit_number' => $candidate]);
                $usedNumbers[strtoupper($candidate)] = true;
            } else {
                $usedNumbers[strtoupper($val)] = true;
            }
        }

        Schema::table('exhibits', function (Blueprint $table) {
            try {
                $table->unique('exhibit_number', 'exhibits_exhibit_number_unique');
            } catch (\Throwable $e) {
                // Unique index may already exist
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('exhibits')) {
            return;
        }

        Schema::table('exhibits', function (Blueprint $table) {
            try {
                $table->dropUnique('exhibits_exhibit_number_unique');
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('exhibits', 'lab_exhibit_no')) {
                $table->dropColumn('lab_exhibit_no');
            }
        });
    }
};

