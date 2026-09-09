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
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->string('title');
            $table->string('unit_name')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('commanding_officer')->nullable();
            $table->string('submitting_officer')->nullable();
            $table->string('suspect_name')->nullable();
            $table->string('priority')->default('NORMAL');
            $table->string('status')->default('Awaiting Sign-off');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->text('exhibits_summary')->nullable();
            $table->string('hash_log')->nullable();
            $table->timestamp('incident_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
