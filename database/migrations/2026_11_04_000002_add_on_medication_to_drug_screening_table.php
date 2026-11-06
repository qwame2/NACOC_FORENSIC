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
            $table->boolean('on_medication')->default(false)->after('notes');
            $table->text('medication_summary')->nullable()->after('on_medication');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drug_screening', function (Blueprint $table) {
            $table->dropColumn(['on_medication', 'medication_summary']);
        });
    }
};

