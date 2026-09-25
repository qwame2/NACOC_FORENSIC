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
        Schema::table('exhibits', function (Blueprint $table) {
            if (!Schema::hasColumn('exhibits', 'exhibit_type')) {
                $table->string('exhibit_type')->nullable()->after('exhibit_category');
            }
            if (!Schema::hasColumn('exhibits', 'drug_classification')) {
                $table->string('drug_classification')->nullable()->after('exhibit_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibits', function (Blueprint $table) {
            if (Schema::hasColumn('exhibits', 'exhibit_type')) {
                $table->dropColumn('exhibit_type');
            }
            if (Schema::hasColumn('exhibits', 'drug_classification')) {
                $table->dropColumn('drug_classification');
            }
        });
    }
};
