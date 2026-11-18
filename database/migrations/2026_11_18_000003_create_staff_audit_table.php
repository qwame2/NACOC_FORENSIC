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
        Schema::create('staff_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('staff_account_id', 64)->nullable()->index();
            $table->string('staff_name')->nullable();
            $table->string('service_id')->nullable()->index();
            $table->string('staff_email')->nullable();
            $table->string('unit')->nullable()->index();
            $table->string('role')->nullable();

            $table->string('action', 50)->index();
            $table->text('description')->nullable();

            $table->string('route')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->integer('status_code')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index(['unit', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_audit');
    }
};

