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
        Schema::table('employees_benefits_monthly', function (Blueprint $table) {
            $table->float('accumulated_value')->after('total_value')->nullable();
            $table->float('saved_value')->after('accumulated_value')->nullable();
            $table->float('final_value')->after('saved_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees_benefits_monthly', function (Blueprint $table) {
            //
        });
    }
};
