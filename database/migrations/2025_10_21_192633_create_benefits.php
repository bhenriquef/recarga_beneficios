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
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();

            $table->string('cod');
            $table->string('description');
            $table->string('region')->nullable();
            $table->string('operator')->nullable();
            $table->float('value', 26, 6)->nullable();
            $table->boolean('variable')->default(false);
            $table->string('type')->nullable();
            $table->boolean('rg')->default(false);
            $table->boolean('birthday')->default(false);
            $table->boolean('mother_name')->default(false);
            $table->boolean('address')->default(false);

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
        Schema::dropIfExists('benefits');
    }
};
