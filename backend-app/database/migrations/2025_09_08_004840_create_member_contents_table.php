<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_contents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            $table->string('type', 32);              // newsletter / event / campaign / survey / ...
            $table->string('title', 255)->nullable();
            $table->longText('body')->nullable();
            $table->json('options')->nullable();

            $table->string('status', 24)->default('draft'); // draft / scheduled / sending / sent / failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['owner_id', 'type']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_contents');
    }
};
