<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sessions')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `sessions` MODIFY `user_id` VARCHAR(64) NULL');
        } else {
            Schema::table('sessions', function (Blueprint $table) {
                $table->string('user_id', 64)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sessions')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::table('sessions')->whereRaw("user_id IS NOT NULL AND user_id NOT REGEXP '^[0-9]+$'")->update(['user_id' => null]);
            DB::statement('ALTER TABLE `sessions` MODIFY `user_id` BIGINT UNSIGNED NULL');
        }
    }
};

