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
        Schema::create('marketplace_resourceables', function (Blueprint $table) {
            $table->id();
            $table->morphs('resourceable', 'mktplc_resource');
            $table->foreignIdFor(\App\Models\Project::class)->index('mkt_project_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_resourceables');
    }
};
