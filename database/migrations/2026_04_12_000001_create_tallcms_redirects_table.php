<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tallcms_redirects')) {
            return;
        }

        Schema::create('tallcms_redirects', function (Blueprint $table) {
            $table->id();
            $table->text('source_path');
            $table->string('source_path_hash', 64)->unique();
            $table->text('destination_url');
            $table->smallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallcms_redirects');
    }
};
