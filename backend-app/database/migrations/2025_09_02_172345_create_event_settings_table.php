<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('type', 16)->default('in_person');
            $table->string('visibility', 16)->default('public');

            $table->string('join_url', 2048)->nullable();
            $table->string('join_platform', 32)->nullable();
            $table->string('join_passcode', 128)->nullable();
            $table->text('join_instructions')->nullable();
            $table->unsignedSmallInteger('join_visible_minutes_before')->default(15);

            $table->string('access_code', 64)->nullable();

            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();

            $table->timestamps();

            $table->unique('event_id');
            $table->index('type');
            $table->index('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_settings');
    }
};
