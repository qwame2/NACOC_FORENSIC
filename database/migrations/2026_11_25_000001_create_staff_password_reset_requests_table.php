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
        if (Schema::hasTable('staff_password_reset_requests')) {
            return;
        }

        Schema::create('staff_password_reset_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_account_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('fullname');
            $table->string('username')->nullable()->index();
            $table->string('service_id', 100)->nullable()->index();
            $table->string('email');
            $table->string('phone_number', 30)->nullable();
            $table->string('unit', 150)->index();
            $table->string('unit_slug', 32)->index();
            $table->string('status', 32)->default('pending')->index(); // pending, approved, declined, completed
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedBigInteger('reviewed_by_id')->nullable()->index();
            $table->string('reviewed_by_name')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_password_reset_requests');
    }
};

