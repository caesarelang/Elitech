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
        Schema::create('logistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('warehouse_name');
            $table->string('warehouse_location', 500)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index untuk optimasi query
            $table->index('product_id');
            $table->index('warehouse_name');
            $table->index(['product_id', 'warehouse_name']);
            $table->index('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics');
    }
};