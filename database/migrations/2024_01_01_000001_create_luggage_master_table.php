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
        Schema::create('luggage_master', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique()->comment('荷物コード');
            $table->string('name', 100)->comment('荷物名');
            $table->string('sub_label', 100)->nullable()->comment('サブラベル');
            $table->foreignUlid('category_id')->constrained('luggage_categories')->onDelete('cascade');
            $table->text('description')->nullable()->comment('説明');
            $table->decimal('base_price', 10, 2)->nullable()->comment('基本料金');
            $table->integer('sort_order')->default(0)->comment('表示順序');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
            
            // インデックス
            $table->index(['code']);
            $table->index(['category_id', 'is_active', 'sort_order']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('luggage_master');
    }
};
