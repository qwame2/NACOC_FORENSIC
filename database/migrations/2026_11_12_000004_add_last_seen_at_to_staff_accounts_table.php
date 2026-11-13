<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_accounts') && !Schema::hasColumn('staff_accounts', 'last_seen_at')) {
            Schema::table('staff_accounts', function (Blueprint $table) {
                $table->timestamp('last_seen_at')->nullable()->after('is_approved');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_accounts') && Schema::hasColumn('staff_accounts', 'last_seen_at')) {
            Schema::table('staff_accounts', function (Blueprint $table) {
                $table->dropColumn('last_seen_at');
            });
        }
    }
};

