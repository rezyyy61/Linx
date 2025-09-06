<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_supporters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('role', 20)->default('follower')->index();
            $table->string('contact_email', 190)->nullable()->index();
            $table->string('contact_phone', 40)->nullable();
            $table->json('tags_json')->nullable();
            $table->timestamp('consent_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id'], 'uq_campaign_supporters_campaign_user');
            $table->unique(['campaign_id', 'contact_email'], 'uq_campaign_supporters_campaign_email');

            $table->foreign('campaign_id', 'fk_campaign_supporters_campaign_id')
                ->references('id')->on('campaigns')->cascadeOnDelete();

            $table->foreign('user_id', 'fk_campaign_supporters_user_id')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_supporters');
    }
};
