<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('avatar_path')->nullable();
            $table->string('email');
            $table->string('recovery_email')->nullable();
            $table->text('password'); // encrypted cast, BUKAN hash cuy
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('job')->nullable();
            $table->string('hobby')->nullable();
            $table->text('context')->nullable();
            $table->string('device_user')->nullable();
            $table->string('device_number')->nullable();
            $table->date('device_released_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('name');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
