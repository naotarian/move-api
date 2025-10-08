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
        Schema::create('stores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name', 100)->comment('店舗名');
            $table->string('email', 255)->unique()->comment('メールアドレス');
            $table->string('password')->comment('パスワード');
            $table->string('phone', 20)->nullable()->comment('電話番号');
            $table->string('address', 255)->nullable()->comment('住所');
            $table->string('license_number', 50)->nullable()->comment('許可番号');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->comment('ステータス');
            $table->boolean('is_verified')->default(false)->comment('認証済みフラグ');
            $table->string('store_default_payment_method_id')->nullable()->comment('店舗既定PM（店舗ごとのオーバーライド）。未設定なら組織既定にフォールバック');
            $table->timestamp('last_login_at')->nullable()->comment('最終ログイン日時');
            $table->rememberToken();
            $table->timestamps();

            // インデックス
            $table->index('email');
            $table->index(['status', 'is_verified']);
            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
