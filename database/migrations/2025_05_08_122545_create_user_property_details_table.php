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
    Schema::create('user_property_details', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('username'); // optional for quick lookup
        $table->string('circle');
        $table->string('pin');
        $table->text('info');
        $table->decimal('latitude', 10, 7);
        $table->decimal('longitude', 10, 7);
        $table->integer('floors_num');
        $table->boolean('basement')->default(false);
        $table->string('land_area');
        $table->string('covered_area');
        $table->string('land')->nullable();
        $table->string('other')->nullable();
        $table->text('comments')->nullable();
        $table->string('picture_path')->nullable();
        $table->timestamp('capture_time')->nullable();
        $table->timestamp('submission_time')->nullable();
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('mobile_users')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_property_details');
    }
};
