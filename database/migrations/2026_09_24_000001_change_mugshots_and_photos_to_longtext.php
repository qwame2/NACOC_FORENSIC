<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('case_suspects', function (Blueprint $table) {
            $table->longText('mugshot_front')->nullable()->change();
            $table->longText('mugshot_left')->nullable()->change();
            $table->longText('mugshot_right')->nullable()->change();
        });

        Schema::table('single_suspect_cases', function (Blueprint $table) {
            $table->longText('mugshot_front')->nullable()->change();
            $table->longText('mugshot_left')->nullable()->change();
            $table->longText('mugshot_right')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('case_suspects', function (Blueprint $table) {
            $table->text('mugshot_front')->nullable()->change();
            $table->text('mugshot_left')->nullable()->change();
            $table->text('mugshot_right')->nullable()->change();
        });

        Schema::table('single_suspect_cases', function (Blueprint $table) {
            $table->text('mugshot_front')->nullable()->change();
            $table->text('mugshot_left')->nullable()->change();
            $table->text('mugshot_right')->nullable()->change();
        });
    }
};
