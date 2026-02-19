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
            // ✅ Add new column for optional second picture
            if (!Schema::hasColumn('user_property_details', 'picture2_path')) {
                $table->string('picture2_path')->nullable()->after('picture_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_property_details', function (Blueprint $table) {
            // ✅ Drop the column if migration is rolled back
            if (Schema::hasColumn('user_property_details', 'picture2_path')) {
                $table->dropColumn('picture2_path');
            }
        });
    }
};
