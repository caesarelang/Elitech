<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quality_control', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained('production_plans')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('total_quantity');
            $table->integer('passed_quantity')->default(0);
            $table->integer('failed_quantity')->default(0);
            $table->foreignId('inspector_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('inspection_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamps();
            
            $table->index(['status', 'inspection_date']);
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quality_control');
    }
};