<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_versions', function (Blueprint $table) {
            $table->string('hash', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_versions', function (Blueprint $table) {
            $table->string('hash', 255)->nullable(false)->change();
        });
    }
};
