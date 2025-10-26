<?php

// database/migrations/xxxx_xx_xx_create_production_plans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->date('target_date');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->text('notes')->nullable();
            
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'rejected',
                'in_progress',
                'completed'
            ])->default('draft');
            
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            
            $table->timestamp('deadline')->nullable();
            $table->timestamp('production_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->integer('progress_percentage')->default(0);
            $table->integer('produced_quantity')->default(0);
            $table->text('progress_notes')->nullable();
            
            $table->timestamps();
            
            $table->index('status');
            $table->index('priority');
            $table->index('target_date');
            $table->index('deadline');
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_plans');
    }
};

