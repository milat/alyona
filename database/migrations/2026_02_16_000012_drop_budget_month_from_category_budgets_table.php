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
        Schema::table('category_budgets', function (Blueprint $table) {
            $table->index('category_id');
            $table->dropUnique(['category_id', 'budget_month']);
            $table->dropColumn('budget_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_budgets', function (Blueprint $table) {
            $table->date('budget_month');
            $table->unique(['category_id', 'budget_month']);
            $table->dropIndex(['category_id']);
        });
    }
};
