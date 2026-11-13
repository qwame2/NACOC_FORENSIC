<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_case_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submitted_by')->nullable()->index();
            $table->string('submitted_by_name')->nullable();
            $table->string('unit_name')->index();
            $table->string('case_number_preview')->nullable();
            $table->string('title')->nullable();
            $table->string('suspect_name')->nullable();
            $table->string('priority')->default('MEDIUM');
            $table->string('status')->default('Pending Approval')->index();
            $table->longText('payload');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('reviewed_by_name')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('approved_case_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_case_submissions');
    }
};

