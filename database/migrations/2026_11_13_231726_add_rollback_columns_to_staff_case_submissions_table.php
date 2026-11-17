<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_case_submissions', function (Blueprint $table) {
            $table->longText('rollback_corrections')->nullable()->after('review_notes');
            $table->timestamp('rolled_back_at')->nullable()->after('rollback_corrections');
        });
    }

    public function down(): void
    {
        Schema::table('staff_case_submissions', function (Blueprint $table) {
            $table->dropColumn(['rollback_corrections', 'rolled_back_at']);
        });
    }
};
