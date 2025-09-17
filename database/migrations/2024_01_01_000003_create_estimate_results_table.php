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
        Schema::create('estimate_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('estimate_id')->constrained('estimates')->onDelete('cascade');
            $table->string('company_name', 100)->comment('業者名');
            $table->string('company_contact', 100)->comment('担当者名');
            $table->string('company_phone', 20)->comment('業者電話番号');
            $table->string('company_email', 255)->comment('業者メールアドレス');
            $table->decimal('estimated_price', 10, 2)->comment('見積もり金額');
            $table->text('notes')->nullable()->comment('備考');
            $table->string('status', 20)->default('pending')->comment('ステータス（pending/accepted/rejected）');
            $table->timestamp('quoted_at')->comment('見積もり日時');
            $table->timestamp('expires_at')->nullable()->comment('有効期限');
            
            $table->timestamps();
            
            // インデックス
            $table->index(['estimate_id', 'status']);
            $table->index(['company_name']);
            $table->index(['quoted_at']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_results');
    }
};
