<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('address');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->dateTime('window_start');
            $table->dateTime('window_end');
            $table->decimal('cod_amount', 12, 2);
            $table->enum('status', ['pending', 'assigned', 'in_transit', 'delivered', 'failed'])
                ->default('pending');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};