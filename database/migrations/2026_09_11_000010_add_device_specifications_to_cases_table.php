<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases', 'dev_model')) {
                $table->text('dev_model')->nullable()->after('seizure_name_device');
                $table->text('dev_serial_no')->nullable()->after('dev_model');
                $table->text('dev_imei1')->nullable()->after('dev_serial_no');
                $table->text('dev_imei2')->nullable()->after('dev_imei1');
                $table->text('dev_mac_address')->nullable()->after('dev_imei2');
                $table->text('dev_storage')->nullable()->after('dev_mac_address');
                $table->text('dev_colour')->nullable()->after('dev_storage');
                $table->text('dev_marks')->nullable()->after('dev_colour');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $columns = [
                'dev_model',
                'dev_serial_no',
                'dev_imei1',
                'dev_imei2',
                'dev_mac_address',
                'dev_storage',
                'dev_colour',
                'dev_marks',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
