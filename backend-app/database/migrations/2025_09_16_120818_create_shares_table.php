<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->string('shareable_type', 160);
            $table->unsignedBigInteger('shareable_id');
            $table->unsignedBigInteger('creator_id')->nullable()->index();
            $table->string('channel', 32)->index();
            $table->string('short_code', 32)->unique();
            $table->string('utm_source', 64)->nullable()->index();
            $table->string('utm_medium', 64)->nullable()->index();
            $table->string('utm_campaign', 128)->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['shareable_type', 'shareable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
