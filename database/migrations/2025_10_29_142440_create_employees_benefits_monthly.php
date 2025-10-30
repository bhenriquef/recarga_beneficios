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
        Schema::create('employees_benefits_monthly', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_benefit_id');
            $table->foreign('employee_benefit_id')->references('id')->on('employees_benefits');

            $table->float('value')->nullable();
            $table->integer('qtd');
            $table->integer('work_days')->nullable();
            $table->float('total_value')->nullable();
            $table->boolean('paid')->default(true);
            $table->date('date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees_benefits_monthly');
    }
};
