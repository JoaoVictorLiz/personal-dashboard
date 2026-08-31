<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('savings_goal_id', 36);
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3);
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('savings_goal_id')
                ->references('id')->on('savings_goals')
                ->cascadeOnDelete();

            $table->index('savings_goal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
