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
        Schema::create('organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100)->comment('組織名');
            $table->string('email', 255)->unique()->comment('メールアドレス');
            $table->string('password')->comment('パスワード');
            $table->string('phone', 20)->nullable()->comment('電話番号');
            $table->string('address', 255)->nullable()->comment('住所');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->comment('ステータス');
            $table->boolean('is_verified')->default(false)->comment('認証済みフラグ');
            $table->string('stripe_customer_id')->nullable()->comment('Stripe顧客ID');
            $table->string('org_default_payment_method_id')->nullable()->comment('店舗に割り当てがない時のフォールバック支払い方法ID');
            $table->timestamp('last_login_at')->nullable()->comment('最終ログイン日時');
            $table->rememberToken();
            $table->timestamps();

            // インデックス
            $table->index('email');
            $table->index(['status', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
