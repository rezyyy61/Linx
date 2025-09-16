<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->foreignId('root_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->string('path', 191)->index();
            $table->text('body');
            $table->enum('status', ['visible', 'pending', 'hidden', 'deleted_soft'])->default('visible')->index();
            $table->unsignedInteger('replies_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['commentable_type', 'commentable_id', 'root_id', 'path'], 'comments_scope_path_idx');
            $table->index(['parent_id']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
