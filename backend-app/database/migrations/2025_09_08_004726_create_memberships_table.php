<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('email')->nullable();
            $table->json('contact_info')->nullable();
            $table->json('meta')->nullable();

            $table->string('status', 24)->default('pending');  // pending / accepted / rejected / blocked
            $table->timestamp('consent_at')->nullable();
            $table->string('rejected_reason', 255)->nullable();

            $table->timestamps();

            $table->unique(['owner_id', 'member_id']);
            $table->unique(['owner_id', 'email']);

            $table->index(['owner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
