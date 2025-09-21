<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'postable_type')) {
                $table->string('postable_type')->nullable()->after('repost_of_id')->index();
            }
            if (! Schema::hasColumn('posts', 'postable_id')) {
                $table->unsignedBigInteger('postable_id')->nullable()->after('postable_type')->index();
            }
            if (! Schema::hasColumn('posts', 'share_id')) {
                $table->unsignedBigInteger('share_id')->nullable()->after('postable_id')->index();
                $table->foreign('share_id')->references('id')->on('shares')->onDelete('set null');
            }
            $table->index(['postable_type', 'postable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'share_id')) {
                $table->dropForeign(['share_id']);
                $table->dropColumn('share_id');
            }
            if (Schema::hasColumn('posts', 'postable_id')) {
                $table->dropIndex(['postable_type', 'postable_id']);
                $table->dropColumn('postable_id');
            }
            if (Schema::hasColumn('posts', 'postable_type')) {
                $table->dropColumn('postable_type');
            }
        });
    }
};
