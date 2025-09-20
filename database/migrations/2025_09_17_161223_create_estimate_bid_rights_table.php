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
        Schema::create('estimate_bid_rights', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // 見積もりID
            $table->foreignUlid('estimate_id')->constrained('estimates')->onDelete('cascade');
            // 店舗ID
            $table->foreignUlid('store_id')->constrained('stores')->onDelete('cascade');
            // ステータス
            $table->tinyInteger('status')->comment('ステータス 1:有効 2:無効');
            // 支払いID
            $table->foreignUlid('payment_id')->constrained('payments')->onDelete('cascade');
            // estimate_idとstore_idの組み合わせでユニーク
            $table->unique(['estimate_id', 'store_id']);
            // インデックス
            $table->index('estimate_id');
            $table->index('store_id');
            $table->index('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_bid_rights');
    }
};
