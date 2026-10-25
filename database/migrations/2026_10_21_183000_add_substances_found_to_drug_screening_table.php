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
        Schema::table('drug_screening', function (Blueprint $table) {
            $table->string('substances_found')->nullable()->after('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drug_screening', function (Blueprint $table) {
            $table->dropColumn('substances_found');
        });
    }
};
