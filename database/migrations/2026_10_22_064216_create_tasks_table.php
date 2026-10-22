<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->date('task_date');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained();
            $table->string('status', 20);      // registered | logged_in
            $table->string('task_status', 20); // todo | progress | done
            $table->text('note')->nullable();
            $table->timestamps();

            // tanpa unique, sama kyk sheet. Duplikat dinetralkan COUNT(DISTINCT)
            $table->index(['user_id', 'task_date']);
            $table->index(['agent_id', 'platform_id']);
            $table->index(['task_date', 'status']); // buat chart dashboard
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
