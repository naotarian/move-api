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
        Schema::create('business_rights', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('estimate_id')->comment('見積もりID');
            $table->ulid('store_id')->comment('店舗ID');
            $table->ulid('bid_id')->comment('入札ID');
            $table->unsignedInteger('bid_amount_min')->comment('入札金額下限');
            $table->unsignedInteger('bid_amount_max')->comment('入札金額上限');
            $table->unsignedTinyInteger('ranking')->comment('順位 (1-3位)');
            $table->boolean('is_notified')->default(false)->comment('通知済みフラグ');
            $table->timestamp('granted_at')->comment('営業権付与日時');
            $table->timestamps();

            // 外部キー制約
            $table->foreign('estimate_id')->references('id')->on('estimates')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('bid_id')->references('id')->on('bids')->onDelete('cascade');

            // インデックス
            $table->index(['estimate_id']);
            $table->index(['store_id']);
            $table->index(['granted_at']);
            $table->index(['is_notified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_rights');
    }
};
