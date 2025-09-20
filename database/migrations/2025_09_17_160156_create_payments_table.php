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
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // 見積もりID
            $table->foreignUlid('estimate_id')->constrained('estimates')->onDelete('cascade');
            // 店舗ID
            $table->foreignUlid('store_id')->constrained('stores')->onDelete('cascade');
            // 支払い金額(税抜き)
            $table->integer('amount_excluding_tax')->comment('支払い金額(税抜き)');
            // 支払い金額(税込み)
            $table->integer('amount_including_tax')->comment('支払い金額(税込み)');
            // 税額
            $table->integer('tax_amount')->comment('税額');
            // 税率
            $table->integer('tax_rate')->comment('税率');
            // 支払い日時
            $table->timestamp('payment_date')->comment('支払い日時');
            // 支払い方法
            $table->tinyInteger('payment_method')->comment('支払い方法 1:クレジットカード 2:銀行振込');
            // 支払いステータス
            $table->tinyInteger('status')->comment('支払いステータス 1:成功 2:失敗');
            // provider
            $table->string('provider')->nullable()->comment('支払いプロバイダー');
            // provider_id
            $table->string('provider_id')->nullable()->comment('支払いプロバイダーID');
            // provider_url
            $table->string('provider_url')->nullable()->comment('支払いプロバイダーURL');
            // 支払い失敗理由
            $table->string('failure_reason')->nullable()->comment('支払い失敗理由');
            // provider_token
            $table->timestamps();
            // インデックス
            $table->index('estimate_id');
            $table->index('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
