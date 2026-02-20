<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_budget_period_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('period_month', 7);
            $table->date('start_date');
            $table->timestamps();

            $table->unique(['household_id', 'period_month'], 'household_period_month_unique');
            $table->index(['household_id', 'start_date'], 'household_start_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_budget_period_overrides');
    }
};
