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
        Schema::table('marketplace_projects', function (Blueprint $table) {
            $table->string('owner')->nullable()->after('name');
            $table->string('owner_type')->nullable()->after('owner');
            $table->string('owner_id')->nullable()->after('owner_type');

            $table->uuid('license_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_projects', function (Blueprint $table) {
            $table->dropColumn(['owner', 'owner_type', 'owner_id']);
            $table->string('license_id')->change();
        });
    }
};
