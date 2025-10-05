<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $t) {
            $t->id();

            $t->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            $t->string('title');
            $t->string('issue');
            $t->text('description')->nullable();

            $t->string('slug')->unique();
            $t->boolean('is_published')->default(false);
            $t->timestamp('publish_at')->nullable();

            $t->string('language', 8)->default('fa');

            $t->timestamps();

            $t->index(['owner_id', 'is_published']);
            $t->index(['issue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
