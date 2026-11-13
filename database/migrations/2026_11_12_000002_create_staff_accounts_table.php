<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('fullname');
            $table->string('username')->unique();
            $table->string('service_id')->unique();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('unit')->index();
            $table->boolean('is_approved')->default(false)->index();
            $table->json('permissions')->nullable();
            $table->string('avatar')->nullable();
            $table->string('signature')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        // Backfill existing staff member accounts from users table
        if (Schema::hasTable('users')) {
            $staffUsers = DB::table('users')
                ->where('is_head', false)
                ->where('is_staff', true)
                ->get();

            foreach ($staffUsers as $u) {
                DB::table('staff_accounts')->updateOrInsert(
                    ['user_id' => $u->id],
                    [
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
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_accounts');
    }
};

