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
        Schema::create('luggage_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique()->comment('カテゴリーコード');
            $table->string('name', 100)->comment('カテゴリー名');
            $table->string('name_en', 100)->nullable()->comment('カテゴリー名（英語）');
            $table->text('description')->nullable()->comment('説明');
            $table->integer('sort_order')->default(0)->comment('表示順序');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
            
            // インデックス
            $table->index(['code']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('luggage_categories');
    }
};
