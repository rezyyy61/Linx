<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_id')->constrained('shares')->cascadeOnDelete();
            $table->string('short_code', 32)->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->text('referer')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_clicks');
    }
};
