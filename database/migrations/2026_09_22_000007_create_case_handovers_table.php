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
        Schema::create('case_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->index();
            $table->foreignId('single_suspect_case_id')->nullable()->constrained('single_suspect_cases')->cascadeOnDelete();
            $table->foreignId('multiple_suspect_case_id')->nullable()->constrained('multiple_suspect_cases')->cascadeOnDelete();
            
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('from_unit');
            
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('to_unit');

            // Pending Acknowledgement, Accepted, Declined, Cancelled
            $table->string('status')->default('Pending Acknowledgement');

            $table->text('request_notes')->nullable();
            $table->text('response_notes')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_handovers');
    }
};
