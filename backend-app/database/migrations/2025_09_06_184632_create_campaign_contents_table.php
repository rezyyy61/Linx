<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->string('type', 50);
            $table->string('channel', 50);
            $table->string('title', 200);
            $table->longText('body')->nullable();
            $table->timestamp('schedule_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('status', 30)->default('draft')->index();
            $table->json('metrics_json')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'channel', 'status'], 'idx_campaign_contents_c_c_s');
            $table->foreign('campaign_id', 'fk_campaign_contents_campaign_id')
                ->references('id')->on('campaigns')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_contents');
    }
};
