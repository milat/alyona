<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->uuid('installment_group_id')->nullable()->after('reference_date');
            $table->unsignedSmallInteger('installment_number')->nullable()->after('installment_group_id');
            $table->unsignedSmallInteger('installments_count')->nullable()->after('installment_number');
            $table->index(['household_id', 'installment_group_id']);
        });

        $purchases = DB::table('purchases')
            ->whereNull('installment_group_id')
            ->orderBy('id')
            ->get();

        $groups = [];

        foreach ($purchases as $purchase) {
            if (! preg_match('/^(.*) ([0-9]+)\/([0-9]+)$/', $purchase->title, $matches)) {
                continue;
            }

            $number = (int) $matches[2];
            $count = (int) $matches[3];

            if ($number < 1 || $count < 2 || $number > $count) {
                continue;
            }

            $key = implode('|', [
                $purchase->household_id,
                $purchase->user_id ?? '',
                $purchase->category_id,
                $purchase->payment_method_id,
                $purchase->credit_card_id ?? '',
                $purchase->purchased_at,
                $matches[1],
                $count,
            ]);

            $groups[$key]['ids'][$number] = $purchase->id;
            $groups[$key]['count'] = $count;
        }

        foreach ($groups as $group) {
            if (count($group['ids']) !== $group['count']) {
                continue;
            }

            $groupId = (string) Str::uuid();

            foreach ($group['ids'] as $number => $purchaseId) {
                DB::table('purchases')
                    ->where('id', $purchaseId)
                    ->update([
                        'installment_group_id' => $groupId,
                        'installment_number' => $number,
                        'installments_count' => $group['count'],
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['household_id', 'installment_group_id']);
            $table->dropColumn(['installment_group_id', 'installment_number', 'installments_count']);
        });
    }
};
