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
        Schema::table('cases', function (Blueprint $table) {
            $table->string('suspect_alias')->nullable()->after('suspect_name');
            $table->string('ghana_card')->nullable()->after('suspect_alias');
            $table->string('gender')->nullable()->after('ghana_card');
            $table->string('dob')->nullable()->after('gender');
            $table->string('marital_status')->nullable()->after('dob');
            $table->text('spouse_info')->nullable()->after('marital_status');
            $table->string('dependents')->nullable()->after('spouse_info');
            $table->string('holding_facility')->nullable()->after('dependents');
            $table->string('exhibit_category')->nullable()->after('holding_facility');
            $table->string('valuation')->nullable()->after('exhibit_category');
            $table->string('operation_code')->nullable()->after('valuation');
            $table->text('mugshot_front')->nullable()->after('operation_code');
            $table->text('mugshot_left')->nullable()->after('mugshot_front');
            $table->text('mugshot_right')->nullable()->after('mugshot_left');
            $table->json('docket_data')->nullable()->after('mugshot_right');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'suspect_alias',
                'ghana_card',
                'gender',
                'dob',
                'marital_status',
                'spouse_info',
                'dependents',
                'holding_facility',
                'exhibit_category',
                'valuation',
                'operation_code',
                'mugshot_front',
                'mugshot_left',
                'mugshot_right',
                'docket_data',
            ]);
        });
    }
};
