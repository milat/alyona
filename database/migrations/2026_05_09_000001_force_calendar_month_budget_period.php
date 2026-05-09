<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('households')->update([
            'budget_period_type' => 'calendar_month',
        ]);
    }

    public function down(): void
    {
        // Historical fifth-business-day configuration is no longer supported.
    }
};
