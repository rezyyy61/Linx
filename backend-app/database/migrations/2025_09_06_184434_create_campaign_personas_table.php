<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_personas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->string('name', 120);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'name']);
            $table->foreign('campaign_id', 'fk_campaign_personas_campaign_id')
                ->references('id')->on('campaigns')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_personas');
    }
};
