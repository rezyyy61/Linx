<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('supporter_id')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('provider', 50)->nullable();
            $table->string('provider_ref', 191)->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status'], 'idx_donations_campaign_status');

            $table->foreign('campaign_id', 'fk_donations_campaign_id')
                ->references('id')->on('campaigns')->cascadeOnDelete();

            $table->foreign('supporter_id', 'fk_donations_supporter_id')
                ->references('id')->on('campaign_supporters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
