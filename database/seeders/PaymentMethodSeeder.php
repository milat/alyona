<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $methods = [
            ['name' => 'Débito', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Crédito', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pix', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Em espécie', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('payment_methods')->upsert(
            $methods,
            ['name'],
            ['updated_at']
        );
    }
}
