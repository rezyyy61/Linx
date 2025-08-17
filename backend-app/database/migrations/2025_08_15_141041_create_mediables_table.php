<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediables', function (Blueprint $table) {
            $table->unsignedBigInteger('media_id');

            $table->unsignedBigInteger('mediable_id');
            $table->string('mediable_type');

            $table->string('collection')->default('default');

            $table->unsignedSmallInteger('order_column')->default(0);

            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id', 'collection']);
            $table->unique(['mediable_type', 'mediable_id', 'media_id', 'collection'], 'mediables_unique');

            $table->foreign('media_id')
                ->references('id')->on('media')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
    }
};
