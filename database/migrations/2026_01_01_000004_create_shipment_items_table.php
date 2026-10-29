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
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->string('item_description', 200);
            $table->integer('quantity')->default(1);
            $table->decimal('weight', 10, 2)->comment('weight in kg');
            $table->decimal('volume', 10, 2)->nullable()->comment('volume in m3');
            $table->string('hs_code', 50)->nullable();
            $table->string('package_type', 50)->default('BOX');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
