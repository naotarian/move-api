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
        Schema::create('admins', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100)->comment('管理者名');
            $table->string('email', 255)->unique()->comment('メールアドレス');
            $table->string('password')->comment('パスワード');
            $table->enum('role', ['super_admin', 'admin', 'viewer'])->default('admin')->comment('権限レベル');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamp('last_login_at')->nullable()->comment('最終ログイン日時');
            $table->rememberToken();
            $table->timestamps();
            
            // インデックス
            $table->index('email');
            $table->index(['is_active', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
