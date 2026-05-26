<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('purchases', 'purchase_group_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropConstrainedForeignId('purchase_group_id');
            });
        }

        Schema::dropIfExists('purchase_groups');
    }

    public function down(): void
    {
        Schema::create('purchase_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('purchase_group_id')
                ->nullable()
                ->after('credit_card_id')
                ->constrained('purchase_groups')
                ->nullOnDelete();
        });
    }
};
