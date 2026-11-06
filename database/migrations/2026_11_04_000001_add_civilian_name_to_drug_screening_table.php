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
            $table->unsignedBigInteger('suspect_id')->nullable()->change();
            $table->string('civilian_name')->nullable()->after('suspect_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drug_screening', function (Blueprint $table) {
            $table->dropColumn('civilian_name');
            $table->unsignedBigInteger('suspect_id')->nullable(false)->change();
        });
    }
};

