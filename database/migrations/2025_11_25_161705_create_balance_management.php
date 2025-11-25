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
        Schema::create('balance_management', function (Blueprint $table) {
            $table->id();

            $table->float('requested_value')->nullable();
            $table->float('accumulated_balance')->nullable();
            $table->float('value_economy')->nullable();
            $table->float('final_order_value')->nullable();
            $table->date('date')->nullable();
            $table->integer('work_days')->nullable();

            $table->unsignedBigInteger('benefits_id');
            $table->foreign('benefits_id')->references('id')->on('benefits');

            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_management');
    }
};
