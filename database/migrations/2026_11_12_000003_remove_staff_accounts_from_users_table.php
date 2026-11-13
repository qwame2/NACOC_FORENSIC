<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_accounts') && !Schema::hasColumn('staff_accounts', 'remember_token')) {
            Schema::table('staff_accounts', function (Blueprint $table) {
                $table->rememberToken()->after('password');
            });
        }

        if (Schema::hasTable('users') && Schema::hasTable('staff_accounts')) {
            $staffUsers = DB::table('users')
                ->where('is_head', false)
                ->where('is_staff', true)
                ->get();

            foreach ($staffUsers as $u) {
                $exists = DB::table('staff_accounts')
                    ->where('user_id', $u->id)
                    ->orWhere('username', $u->username)
                    ->orWhere('service_id', $u->service_id)
                    ->exists();

                if (!$exists) {
                    DB::table('staff_accounts')->insert([
                        'user_id' => null,
                        'fullname' => $u->fullname,
                        'username' => $u->username,
                        'service_id' => $u->service_id,
                        'phone_number' => $u->phone_number ?? null,
                        'email' => $u->email ?? null,
                        'unit' => $u->unit,
                        'is_approved' => (bool) ($u->is_approved ?? false),
                        'permissions' => $u->permissions ?? null,
                        'avatar' => $u->avatar ?? null,
                        'signature' => $u->signature ?? null,
                        'password' => $u->password ?? null,
                        'created_at' => $u->created_at ?? now(),
                        'updated_at' => $u->updated_at ?? now(),
                    ]);
                }
            }

            // Detach staff_accounts from users before deleting staff rows from users
            DB::table('staff_accounts')->update(['user_id' => null]);

            // Remove all staff member accounts from users table
            DB::table('users')
                ->where('is_head', false)
                ->where('is_staff', true)
                ->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_accounts') && Schema::hasColumn('staff_accounts', 'remember_token')) {
            Schema::table('staff_accounts', function (Blueprint $table) {
                $table->dropRememberToken();
            });
        }
    }
};

