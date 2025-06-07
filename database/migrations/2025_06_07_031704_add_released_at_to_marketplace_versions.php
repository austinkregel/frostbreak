<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketplace_versions', function (Blueprint $table) {
            $table->dateTime('released_at')
                ->nullable()
                ->after('hash')
                ->comment('The date and time when the version was released');
            $table->string('dist_url', 2048)
                ->nullable()
                ->after('released_at')
                ->comment('The URL to the distribution package of the version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_versions', function (Blueprint $table) {
            $table->dropColumn('released_at');
            $table->dropColumn('dist_url');
        });
    }
};
