<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_stage_credits', function (Blueprint $table) {
            $table->id();
            // Snapshot IDs preserve attribution after source records are deleted.
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('platform_id');
            $table->string('platform_slug');
            $table->enum('stage', ['registered', 'logged_in']);
            $table->unsignedBigInteger('inputer_id');
            $table->dateTime('achieved_at');
            $table->unique(['agent_id', 'platform_id', 'stage']);
            $table->index(['inputer_id', 'achieved_at']);
            $table->index('achieved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_stage_credits');
    }
};
