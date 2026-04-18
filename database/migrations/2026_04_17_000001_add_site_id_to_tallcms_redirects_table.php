<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tallcms_redirects', 'site_id')) {
            return;
        }

        Schema::table('tallcms_redirects', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')
                ->constrained('tallcms_sites')->nullOnDelete();
            $table->index('site_id');

            // Replace global unique on source_path_hash with site-scoped unique.
            // Two sites should be able to redirect the same path.
            try {
                $table->dropUnique(['source_path_hash']);
            } catch (\Throwable) {
                // Index name may differ or not exist on SQLite
            }
            $table->unique(['site_id', 'source_path_hash']);
        });

        // Backfill: assign to default site
        $defaultSiteId = DB::table('tallcms_sites')->where('is_default', true)->value('id');
        if ($defaultSiteId) {
            DB::table('tallcms_redirects')->whereNull('site_id')->update(['site_id' => $defaultSiteId]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tallcms_redirects', 'site_id')) {
            return;
        }

        Schema::table('tallcms_redirects', function (Blueprint $table) {
            try {
                $table->dropUnique(['site_id', 'source_path_hash']);
            } catch (\Throwable) {
            }

            $table->unique('source_path_hash');

            try {
                $table->dropConstrainedForeignId('site_id');
            } catch (\Throwable) {
                $table->dropColumn('site_id');
            }
        });
    }
};
