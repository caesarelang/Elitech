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
        Schema::table('quality_control', function (Blueprint $table) {
            // Tambah kolom untuk tracking sync status
            $table->boolean('synced_to_logistics')->default(false)->after('status');
            $table->timestamp('synced_at')->nullable()->after('synced_to_logistics');
            
            // Index untuk performa query
            $table->index(['status', 'synced_to_logistics']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quality_control', function (Blueprint $table) {
            $table->dropIndex(['status', 'synced_to_logistics']);
            $table->dropColumn(['synced_to_logistics', 'synced_at']);
        });
    }
};