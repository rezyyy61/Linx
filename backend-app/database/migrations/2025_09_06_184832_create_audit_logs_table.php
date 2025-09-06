<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 100)->index();
            $table->string('model', 160)->index();
            $table->unsignedBigInteger('model_id')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['model', 'model_id', 'action'], 'idx_audit_model_modelid_action');
            $table->foreign('user_id', 'fk_audit_logs_user_id')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
