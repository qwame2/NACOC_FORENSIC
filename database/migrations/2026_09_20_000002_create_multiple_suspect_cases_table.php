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
        Schema::create('multiple_suspect_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->string('laboratory_number')->nullable();
            $table->text('title')->nullable();
            $table->string('unit_name')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('commanding_officer')->nullable();
            $table->string('submitting_officer')->nullable();
            
            // Operation & Location Details
            $table->string('operation_code')->nullable();
            $table->string('operation_code_name')->nullable();
            $table->text('raid_location')->nullable();
            $table->text('offense_description')->nullable();
            $table->string('incident_date')->nullable();

            // Case Summary Stats
            $table->text('docket_summary')->nullable();
            $table->integer('total_suspects_count')->default(0);
            $table->integer('total_exhibits_count')->default(0);

            // Status & Priority
            $table->string('priority')->default('NORMAL');
            $table->string('status')->default('Approved');
            $table->text('hash_log')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multiple_suspect_cases');
    }
};
