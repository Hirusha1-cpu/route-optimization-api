<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Append-only: no updated_at column
    public function up(): void
    {
        Schema::create('cod_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
            $table->decimal('amount_collected', 12, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cod_ledger');
    }
};