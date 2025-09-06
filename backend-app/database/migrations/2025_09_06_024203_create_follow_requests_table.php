<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->tinyInteger('pending_flag')->nullable();
            $table->unsignedBigInteger('pair_low');
            $table->unsignedBigInteger('pair_high');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['target_id', 'status'], 'follow_requests_target_status_idx');
            $table->index(['pair_low', 'pair_high'], 'follow_requests_pair_idx');
            $table->unique(['pair_low', 'pair_high', 'pending_flag'], 'follow_requests_unique_pending_pair');
        });

        DB::statement('ALTER TABLE follow_requests ADD CONSTRAINT chk_follow_requests_not_self CHECK (actor_id <> target_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_requests');
    }
};
