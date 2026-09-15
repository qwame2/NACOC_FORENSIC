<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add database indexes to 'cases' table
        Schema::table('cases', function (Blueprint $table) {
            $table->index('status', 'cases_status_index');
            $table->index('laboratory_number', 'cases_laboratory_number_index');
            $table->index('priority', 'cases_priority_index');
            $table->index('created_at', 'cases_created_at_index');
            $table->index('unit_name', 'cases_unit_name_index');
            $table->index('suspect_name', 'cases_suspect_name_index');
            $table->index(['status', 'created_at'], 'cases_status_created_at_index');
        });

        // 2. Add database indexes to 'users' table
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_approved', 'users_is_approved_index');
            $table->index('is_head', 'users_is_head_index');
            $table->index(['is_approved', 'is_head'], 'users_approved_head_index');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropIndex('cases_status_index');
            $table->dropIndex('cases_laboratory_number_index');
            $table->dropIndex('cases_priority_index');
            $table->dropIndex('cases_created_at_index');
            $table->dropIndex('cases_unit_name_index');
            $table->dropIndex('cases_suspect_name_index');
            $table->dropIndex('cases_status_created_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_approved_index');
            $table->dropIndex('users_is_head_index');
            $table->dropIndex('users_approved_head_index');
        });
    }
};
