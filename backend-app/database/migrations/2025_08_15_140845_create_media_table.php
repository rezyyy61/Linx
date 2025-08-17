<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('disk')->default('s3');

            $table->string('key')->unique();

            $table->string('type')->default(MediaType::IMAGE->value)->index();

            $table->string('status')->default(MediaStatus::UPLOADED->value)->index();

            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime', 191)->nullable();
            $table->string('ext', 50)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->decimal('duration', 8, 2)->nullable();
            $table->char('sha256', 64)->nullable()->index();

            $table->json('meta')->nullable();
            $table->json('processed')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
