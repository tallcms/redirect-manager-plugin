<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repair migration for databases that already ran the site_id migration
 * before the uniqueness fix was added. Replaces the global source_path_hash
 * unique index with a site-scoped composite unique.
 *
 * Safe to run on fresh installs — detects if the fix is already applied.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tallcms_redirects', 'site_id')) {
            return;
        }

        // Check if the global unique index still exists.
        // If so, this database ran the old site_id migration without the fix.
        if (! $this->hasGlobalUniqueIndex()) {
            return; // Already has composite unique (fresh install)
        }

        // Drop global unique and add site-scoped composite
        try {
            Schema::table('tallcms_redirects', function (Blueprint $table) {
                $table->dropUnique(['source_path_hash']);
            });
        } catch (\Throwable) {
            // Index naming may differ — try raw SQL
            try {
                DB::statement('DROP INDEX IF EXISTS tallcms_redirects_source_path_hash_unique');
            } catch (\Throwable) {
            }
        }

        try {
            Schema::table('tallcms_redirects', function (Blueprint $table) {
                $table->unique(['site_id', 'source_path_hash']);
            });
        } catch (\Throwable) {
            // Composite unique may already exist
        }
    }

    public function down(): void
    {
        // No-op — the original migration's down() handles full reversal
    }

    protected function hasGlobalUniqueIndex(): bool
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list('tallcms_redirects')");
                foreach ($indexes as $index) {
                    if (str_contains($index->name, 'source_path_hash') && $index->unique) {
                        // Check if it's the global one (single column) vs composite
                        $cols = DB::select("PRAGMA index_info('{$index->name}')");
                        if (count($cols) === 1) {
                            return true; // Single-column unique = global
                        }
                    }
                }

                return false;
            }

            if ($driver === 'pgsql') {
                // PostgreSQL: query pg_indexes for unique indexes on the table
                $indexes = DB::select("
                    SELECT indexname, indexdef
                    FROM pg_indexes
                    WHERE tablename = 'tallcms_redirects'
                    AND indexdef LIKE '%UNIQUE%'
                    AND indexdef LIKE '%source_path_hash%'
                ");

                foreach ($indexes as $index) {
                    // A single-column unique won't mention site_id in its definition
                    if (! str_contains($index->indexdef, 'site_id')) {
                        return true; // Global unique (single column)
                    }
                }

                return false;
            }

            // MySQL/MariaDB
            $indexes = DB::select("SHOW INDEX FROM tallcms_redirects WHERE Column_name = 'source_path_hash' AND Non_unique = 0");

            foreach ($indexes as $index) {
                $indexName = $index->Key_name ?? $index->key_name ?? '';
                $allCols = DB::select("SHOW INDEX FROM tallcms_redirects WHERE Key_name = ?", [$indexName]);
                if (count($allCols) === 1) {
                    return true; // Single-column unique = global
                }
            }
        } catch (\Throwable) {
        }

        return false;
    }
};
