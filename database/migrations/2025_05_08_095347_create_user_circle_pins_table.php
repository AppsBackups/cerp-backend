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
        Schema::create('user_circle_pins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('circle');
            $table->string('pin');

            // New columns
            $table->string('ratingarea')->nullable();
            $table->string('Locality')->nullable();
            $table->string('Block')->nullable();
            $table->string('Street_Address')->nullable();
            $table->string('OwnerName')->nullable();
            $table->string('Road')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('mobile_users')->onDelete('cascade');

            // Unique constraint on user_id + pin
            $table->unique(['user_id', 'pin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_circle_pins');
    }
};
