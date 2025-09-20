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
        Schema::create('bids', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // 入札権ID
            $table->foreignUlid('estimate_bid_right_id')->constrained('estimate_bid_rights')->onDelete('cascade');
            // 入札金額(下限)
            $table->integer('bid_amount_min')->comment('入札金額(下限)');
            // 入札金額(上限)
            $table->integer('bid_amount_max')->comment('入札金額(上限)');
            // 入札日時
            $table->timestamp('bid_at')->comment('入札日時');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
