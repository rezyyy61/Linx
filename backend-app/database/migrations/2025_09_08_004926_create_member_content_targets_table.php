<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_content_targets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('content_id')->constrained('member_contents')->cascadeOnDelete();
            $table->foreignId('membership_id')->constrained('memberships')->cascadeOnDelete();

            $table->string('channel', 24)->default('email');   // email / sms / push ...
            $table->string('status', 24)->default('pending');  // pending / sent / failed / opened / clicked

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            $table->string('error', 500)->nullable();
            $table->json('delivery_meta')->nullable();         // messageId، provider، ...

            $table->timestamps();

            $table->unique(['content_id', 'membership_id', 'channel']);
            $table->index(['status', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_content_targets');
    }
};
