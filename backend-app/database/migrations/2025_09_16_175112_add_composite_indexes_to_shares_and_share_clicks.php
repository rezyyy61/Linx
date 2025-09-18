<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->index(['is_active', 'expires_at'], 'shares_is_active_expires_at_index');
            $table->index(['shareable_type', 'shareable_id', 'channel'], 'shares_shareable_channel_index');
        });

        Schema::table('share_clicks', function (Blueprint $table) {
            $table->index(['share_id', 'occurred_at'], 'share_clicks_share_id_occurred_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropIndex('shares_is_active_expires_at_index');
            $table->dropIndex('shares_shareable_channel_index');
        });

        Schema::table('share_clicks', function (Blueprint $table) {
            $table->dropIndex('share_clicks_share_id_occurred_at_index');
        });
    }
};
