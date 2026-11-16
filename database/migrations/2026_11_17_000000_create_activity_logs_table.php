<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Snapshot IDs/names preserve attribution after source records are deleted.
            $table->unsignedBigInteger('inputer_id');
            $table->string('inputer_name');
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->enum('subject_type', ['agent', 'account']);
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('agent_id');
            $table->string('agent_name');
            $table->unsignedBigInteger('platform_id')->nullable();
            $table->string('platform_slug')->nullable();
            $table->string('platform_name')->nullable();
            $table->string('identifier')->nullable();
            $table->dateTime('occurred_at');
            $table->index('occurred_at');
            $table->index(['inputer_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
