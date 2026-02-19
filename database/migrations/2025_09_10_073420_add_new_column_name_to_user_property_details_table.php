<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('user_property_details', function (Blueprint $table) {
            $table->integer('Store_front')->default(0)->after('resubmission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('user_property_details', function (Blueprint $table) {
            $table->dropColumn('Store_front');
        });
    }
};