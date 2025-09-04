<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
            $table->string('timezone', 64)->default('Europe/Amsterdam')->after('ends_at');
            $table->timestamp('publish_at')->nullable()->after('is_published');
        });

        DB::transaction(function () {
            $rows = DB::table('events')->select('id', 'title', 'slug')->get();
            foreach ($rows as $r) {
                if ($r->slug) {
                    continue;
                }
                $base = Str::slug($r->title ?: ('event-'.$r->id));
                if ($base === '') {
                    $base = 'event-'.$r->id;
                }
                $slug = $base;
                $i = 2;
                while (DB::table('events')->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                DB::table('events')->where('id', $r->id)->update(['slug' => $slug]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['slug', 'timezone', 'publish_at']);
        });
    }
};
