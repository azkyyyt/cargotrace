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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('carrier_id')->constrained('carriers')->onDelete('restrict');
            $table->foreignId('origin_id')->constrained('locations')->onDelete('restrict');
            $table->foreignId('destination_id')->constrained('locations')->onDelete('restrict');
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->onDelete('set null');
            
            $table->string('cargo_type', 100);
            $table->decimal('weight', 12, 2)->comment('in Metric Tons or kg');
            $table->decimal('volume', 12, 2)->nullable()->comment('in m3 (CBM)');

            // Simulated Payment Gateway & Freight Booking Fields
            $table->enum('payment_status', ['UNPAID', 'PENDING', 'PAID'])->default('PAID')->index();
            $table->string('payment_method', 50)->nullable()->comment('QRIS, VIRTUAL_ACCOUNT, LETTER_OF_CREDIT');
            $table->string('payment_reference', 100)->nullable();
            $table->decimal('freight_charge_usd', 12, 2)->default(0.00);
            
            $table->dateTime('expected_departure');
            $table->dateTime('expected_arrival');
            $table->dateTime('actual_departure')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->dateTime('estimated_arrival');
            
            $table->enum('current_status', [
                'CREATED',
                'BOOKED',
                'PICKED_UP',
                'IN_TRANSIT',
                'DELIVERED',
                'CANCELLED'
            ])->default('CREATED')->index();

            $table->enum('delay_status', ['ON_TIME', 'DELAYED'])->default('ON_TIME')->index();
            $table->integer('delay_duration_hours')->default(0);
            $table->enum('delay_severity', ['NONE', 'LOW', 'MEDIUM', 'HIGH'])->default('NONE');
            $table->text('delay_reason')->nullable();
            
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
