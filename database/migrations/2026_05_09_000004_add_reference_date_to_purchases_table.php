<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('reference_date')->nullable()->after('purchased_at');
            $table->index(['household_id', 'reference_date']);
        });

        DB::table('purchases')
            ->leftJoin('credit_cards', 'purchases.credit_card_id', '=', 'credit_cards.id')
            ->select([
                'purchases.id as purchase_id',
                'purchases.purchased_at',
                'purchases.credit_card_id',
                'credit_cards.closing_day',
            ])
            ->orderBy('purchases.id')
            ->chunkById(200, function ($purchases) {
                foreach ($purchases as $purchase) {
                    $purchasedAt = Carbon::parse($purchase->purchased_at)->startOfDay();
                    $referenceDate = $purchasedAt->copy()->startOfMonth();

                    if ($purchase->credit_card_id !== null && $purchase->closing_day !== null && $purchasedAt->day >= (int) $purchase->closing_day) {
                        $referenceDate = $referenceDate->addMonthNoOverflow();
                    }

                    DB::table('purchases')
                        ->where('id', $purchase->purchase_id)
                        ->update(['reference_date' => $referenceDate->toDateString()]);
                }
            }, 'purchases.id', 'purchase_id');
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['household_id', 'reference_date']);
            $table->dropColumn('reference_date');
        });
    }
};
