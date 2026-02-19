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
        $table->tinyInteger('resubmission')->default(0)->after('submission_time');
    });
}

public function down()
{
    Schema::table('user_property_details', function (Blueprint $table) {
        $table->dropColumn('resubmission');
    });
}

};
