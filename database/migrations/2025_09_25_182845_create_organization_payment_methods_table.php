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
        Schema::create('organization_payment_methods', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();

            $table->string('stripe_payment_method_id')->unique()->comment('Stripe支払い方法ID');
            $table->string('label')->nullable()->comment('支払い方法ラベル');

            $table->string('type', 32)->default('card')->comment('支払い方法タイプ');
            $table->string('brand', 32)->nullable()->comment('カードブランド');
            $table->string('last4', 4)->nullable()->comment('カード最後4桁');

            // 数値型の方が安全（ソート/比較）
            $table->unsignedSmallInteger('exp_year')->nullable()->comment('カード有効期限年');
            $table->unsignedTinyInteger('exp_month')->nullable()->comment('カード有効期限月');

            $table->string('fingerprint')->nullable()->comment('カードフィンガープリント');

            $table->enum('status', ['active', 'inactive'])->default('active')->comment('支払い方法ステータス');

            $table->string('billing_name')->nullable()->comment('請求先名');
            $table->string('billing_zipcode')->nullable()->comment('請求先郵便番号');
            $table->string('billing_address')->nullable()->comment('請求先住所');
            $table->string('billing_email')->nullable()->comment('請求先メールアドレス');

            $table->timestamps();
            $table->softDeletes();

            // よく使う検索
            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_payment_methods', function (Blueprint $table) {
            $table->dropUnique('org_fingerprint_unique');
        });
        Schema::dropIfExists('organization_payment_methods');
    }
};
