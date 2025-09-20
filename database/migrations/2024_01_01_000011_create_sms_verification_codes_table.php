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
        Schema::create('sms_verification_codes', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // 関連情報
            $table->ulid('estimate_id')->comment('見積もりID');
            $table->foreign('estimate_id')->references('id')->on('estimates')->onDelete('cascade');

            // SMS認証情報
            $table->string('phone', 20)->comment('認証対象の電話番号');
            $table->string('code', 6)->comment('認証コード（6桁数字）');

            // ステータス・有効期限
            $table->enum('status', ['pending', 'used', 'expired'])->default('pending')->comment('ステータス（未使用/使用済み/期限切れ）');
            $table->timestamp('expires_at')->comment('有効期限');
            $table->timestamp('used_at')->nullable()->comment('使用日時');

            // 送信情報
            $table->json('send_result')->nullable()->comment('送信結果（AWS SNS MessageID等）');
            $table->unsignedTinyInteger('retry_count')->default(0)->comment('再送回数');
            $table->timestamp('last_sent_at')->nullable()->comment('最終送信日時');

            $table->timestamps();

            // インデックス
            $table->index(['estimate_id']);
            $table->index(['code']);
            $table->index(['phone']);
            $table->index(['status', 'expires_at']);
            $table->index(['expires_at']);
            $table->index(['created_at']);

            // 注意: MySQLでは条件付きユニーク制約が直接サポートされていないため、
            // アプリケーションロジックで制御する
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_verification_codes');
    }
};
