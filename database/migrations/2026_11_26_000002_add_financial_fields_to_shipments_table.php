<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'commodity_price_usd')) {
                $table->decimal('commodity_price_usd', 14, 2)->default(0.00)->after('freight_charge_usd');
            }
            if (!Schema::hasColumn('shipments', 'vessel_fee_usd')) {
                $table->decimal('vessel_fee_usd', 14, 2)->default(0.00)->after('commodity_price_usd');
            }
            if (!Schema::hasColumn('shipments', 'gross_amount_usd')) {
                $table->decimal('gross_amount_usd', 14, 2)->default(0.00)->after('vessel_fee_usd');
            }
            if (!Schema::hasColumn('shipments', 'net_revenue_usd')) {
                $table->decimal('net_revenue_usd', 14, 2)->default(0.00)->after('gross_amount_usd');
            }
            if (!Schema::hasColumn('shipments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $cols = ['commodity_price_usd', 'vessel_fee_usd', 'gross_amount_usd', 'net_revenue_usd', 'paid_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('shipments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
