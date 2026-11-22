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
            $table->string('grade_specification', 150)->nullable()->comment('e.g. Non-GMO Grade 2, Protein 12.5%');
            $table->integer('quantity')->default(1);
            $table->decimal('weight', 12, 2)->comment('weight in MT or kg');
            $table->decimal('volume', 12, 2)->nullable()->comment('volume in m3');
            $table->decimal('moisture_content_percent', 5, 2)->nullable()->comment('Grain moisture %');
            $table->string('hs_code', 50)->nullable();
            $table->string('package_type', 50)->default('BULK_HOLD');
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
