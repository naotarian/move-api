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
        Schema::create('estimates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            
            // 基本情報
            $table->string('name', 100)->comment('お名前');
            $table->string('name_furigana', 100)->comment('フリガナ');
            $table->string('phone', 20)->comment('電話番号');
            $table->string('email', 255)->comment('メールアドレス');
            
            // 人数・日程・時間
            $table->tinyInteger('people_count')->comment('引越し人数（1-4、4は4人以上）');
            $table->enum('moving_date_type', ['undecided', 'decided'])->comment('引越し日タイプ（決まっていない/決まっている）');
            $table->string('moving_year_month', 10)->nullable()->comment('引越し年月');
            $table->enum('moving_period', ['early', 'middle', 'late'])->nullable()->comment('引越し期間（上旬/中旬/下旬）');
            $table->date('moving_specific_date')->nullable()->comment('引越し具体的日付');
            $table->enum('work_start_time_type', ['anytime', 'specific'])->comment('作業開始時間タイプ（いつでも/指定する）');
            $table->enum('work_start_time', ['morning', 'afternoon', 'evening'])->nullable()->comment('作業開始時間（午前中/12時~15時/15時以降）');
            
            
            // その他の家財
            $table->text('other_luggage')->nullable()->comment('上記以外の家財');
            
            // システム情報
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft')->comment('ステータス（公開前/公開中/公開終了）');
            
            $table->timestamps();
            
            // インデックス
            $table->index(['status', 'created_at']);
            $table->index(['email', 'created_at']);
            $table->index(['moving_specific_date']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
