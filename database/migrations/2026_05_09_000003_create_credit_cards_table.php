<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedTinyInteger('closing_day');
            $table->decimal('limit', 12, 2)->nullable();
            $table->text('observation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('credit_card_id')
                ->nullable()
                ->after('payment_method_id')
                ->constrained('credit_cards')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_card_id');
        });

        Schema::dropIfExists('credit_cards');
    }
};
