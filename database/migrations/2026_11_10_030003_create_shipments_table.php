<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('carrier_id')->constrained('carriers')->restrictOnDelete();
            $table->foreignId('origin_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_id')->constrained('locations')->restrictOnDelete();
            $table->string('cargo_type', 50)->default('GENERAL');
            $table->decimal('weight', 10, 2); // kg
            $table->decimal('volume', 10, 2)->nullable(); // cbm
            $table->string('current_status', 30)->default('CREATED');
            
            $table->dateTime('expected_departure');
            $table->dateTime('actual_departure')->nullable();
            $table->dateTime('expected_arrival');
            $table->dateTime('estimated_arrival')->nullable();
            $table->dateTime('actual_arrival')->nullable();

            $table->boolean('is_delayed')->default(false);
            $table->integer('delay_duration_minutes')->default(0);
            $table->string('delay_severity', 20)->default('NONE'); // NONE, LOW, MEDIUM, HIGH
            $table->text('delay_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('current_status');
            $table->index('is_delayed');
            $table->index('expected_arrival');
            $table->index(['customer_id', 'current_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
