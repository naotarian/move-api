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
        Schema::create('estimate_luggage', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('estimate_id')->constrained('estimates')->onDelete('cascade');
            $table->foreignUlid('luggage_id')->constrained('luggage_master')->onDelete('cascade');
            $table->integer('quantity')->default(0)->comment('数量');
            
            $table->timestamps();
            
            // インデックス
            $table->index(['estimate_id', 'luggage_id']);
            $table->index(['luggage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_luggage');
    }
};
