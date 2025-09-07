<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('donation_intents')) {
            Schema::create('donation_intents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
                $table->string('requester_email')->index();
                $table->decimal('amount', 12, 2);
                $table->char('currency', 3)->default('USD');
                $table->text('message')->nullable();
                $table->string('status')->default('pending'); // pending|email_sent|paid|cancelled|failed
                $table->uuid('token')->unique();
                $table->dateTime('sent_at')->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_intents');
    }
};
