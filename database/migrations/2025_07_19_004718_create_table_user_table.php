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
        Schema::create('table_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('table_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('chips');
            $table->timestamps();

            $table->foreign('table_id')->references('id')->on('tables')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_user');
    }
};
