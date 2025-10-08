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
        Schema::create('moving_from_addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('estimate_id')->constrained('estimates')->onDelete('cascade');
            $table->string('zipcode', 10)->comment('郵便番号');
            $table->string('prefecture', 50)->comment('都道府県');
            $table->integer('prefecture_code')->nullable()->comment('都道府県コード (1-47)');
            $table->integer('region_code')->nullable()->comment('地域コード (1-8)');
            $table->string('street_address', 255)->comment('番地');
            $table->string('building_details', 255)->nullable()->comment('建物名・部屋番号');
            $table->string('building_type', 50)->comment('建物のタイプ');
            $table->string('room_layout', 50)->comment('間取り');
            $table->string('floor', 20)->comment('お住まいの階数');
            $table->string('elevator', 10)->comment('エレベーター');
            $table->decimal('latitude', 10, 8)->nullable()->comment('緯度');
            $table->decimal('longitude', 11, 8)->nullable()->comment('経度');
            $table->timestamps();

            // インデックス
            $table->index('estimate_id', 'idx_moving_from_addresses_estimate_id');
            $table->index(['prefecture', 'zipcode'], 'idx_moving_from_addresses_location');
            $table->index('prefecture_code', 'idx_moving_from_addresses_prefecture_code');
            $table->index('region_code', 'idx_moving_from_addresses_region_code');
            $table->index(['region_code', 'prefecture_code'], 'idx_moving_from_addresses_region_prefecture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moving_from_addresses');
    }
};
