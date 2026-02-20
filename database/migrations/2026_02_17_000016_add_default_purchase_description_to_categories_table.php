<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('default_purchase_description')->nullable()->after('description');
            $table->unique(['household_id', 'default_purchase_description'], 'categories_household_default_purchase_unique');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_household_default_purchase_unique');
            $table->dropColumn('default_purchase_description');
        });
    }
};
