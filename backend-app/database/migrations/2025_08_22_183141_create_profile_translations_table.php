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
        Schema::create('profile_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('tagline')->nullable();
            $table->text('about')->nullable();
            $table->text('goals')->nullable();
            $table->text('activities')->nullable();
            $table->text('structure')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_translations');
    }
};
