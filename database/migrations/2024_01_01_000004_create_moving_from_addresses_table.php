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
            $table->string('street_address', 255)->comment('番地');
            $table->string('building_details', 255)->nullable()->comment('建物名・部屋番号');
            $table->string('building_type', 50)->comment('建物のタイプ');
            $table->string('room_layout', 50)->comment('間取り');
            $table->string('floor', 20)->comment('お住まいの階数');
            $table->string('elevator', 10)->comment('エレベーター');
            $table->timestamps();
            
            // インデックス
            $table->index('estimate_id', 'idx_moving_from_addresses_estimate_id');
            $table->index(['prefecture', 'zipcode'], 'idx_moving_from_addresses_location');
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
