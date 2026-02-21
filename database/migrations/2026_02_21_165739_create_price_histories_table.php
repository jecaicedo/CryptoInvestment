<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cryptocurrency_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 20, 8);
            $table->decimal('percent_change_1h', 8, 4)->nullable();
            $table->decimal('percent_change_24h', 8, 4)->nullable();
            $table->decimal('percent_change_7d', 8, 4)->nullable();
            $table->decimal('volume_24h', 30, 2)->nullable();
            $table->decimal('market_cap', 30, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};