<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('title');
            $table->unsignedBigInteger('target_amount_cents');
            $table->unsignedBigInteger('current_amount_cents')->default(0);
            $table->char('currency', 3);
            $table->string('status', 20);
            $table->date('target_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_goals');
    }
};
