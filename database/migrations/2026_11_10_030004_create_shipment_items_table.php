<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->string('unit', 30)->default('CARTONS'); // CARTONS, PALLETS, PIECES, CONTAINERS
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->string('hs_code', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
