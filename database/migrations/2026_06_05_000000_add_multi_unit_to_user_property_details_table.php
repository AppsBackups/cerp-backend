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
        Schema::table('user_property_details', function (Blueprint $table) {
            if (!Schema::hasColumn('user_property_details', 'multi_unit')) {
                $table->boolean('multi_unit')->default(false)->after('Store_front');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_property_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_property_details', 'multi_unit')) {
                $table->dropColumn('multi_unit');
            }
        });
    }
};
