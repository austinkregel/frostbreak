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
        Schema::table('marketplace_packages', function (Blueprint $table) {
            $table->json('keywords')
                ->nullable()
                ->after('needs_additional_processing')
                ->comment('Keywords associated with the package for search and categorization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_packages', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });
    }
};
