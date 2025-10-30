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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->string('full_name');
            $table->string('cod_solides')->nullable();
            $table->string('cod_vr')->nullable();
            $table->string('email')->nullable();
            $table->string('cpf')->nullable();
            $table->string('rg')->nullable();
            $table->date('birthday')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('address')->nullable();
            $table->boolean('holiday_next_month')->default(false);
            $table->boolean('recurring_absence')->default(false);
            $table->boolean('shutdown_programming')->default(false);

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
