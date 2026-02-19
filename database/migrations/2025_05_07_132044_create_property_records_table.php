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
        Schema::create('property_records', function (Blueprint $table) {
            $table->id();
            $table->string('pin');
            $table->string('ratingarea');
            $table->string('circle');
            $table->string('Locality');
            $table->string('Block')->nullable();
            $table->string('Street_Address')->nullable();
            $table->string('OwnerName')->nullable();
            $table->string('Road')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_records');
    }
};
