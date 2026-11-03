<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exhibits', function (Blueprint $table) {
            if (!Schema::hasColumn('exhibits', 'custodial_status')) {
                $table->string('custodial_status', 50)->default('Seized')->after('property_id');
            }
            if (!Schema::hasColumn('exhibits', 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->unique()->after('tag_url');
            }
        });

        // Populate qr_token for any existing exhibits that don't have one
        $exhibits = DB::table('exhibits')->whereNull('qr_token')->get();
        foreach ($exhibits as $exhibit) {
            DB::table('exhibits')
                ->where('id', $exhibit->id)
                ->update([
                    'qr_token' => (string) Str::uuid(),
                    'custodial_status' => $exhibit->custodial_status ?? 'Seized',
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibits', function (Blueprint $table) {
            if (Schema::hasColumn('exhibits', 'qr_token')) {
                $table->dropUnique(['qr_token']);
                $table->dropColumn('qr_token');
            }
            if (Schema::hasColumn('exhibits', 'custodial_status')) {
                $table->dropColumn('custodial_status');
            }
        });
    }
};
