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
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('github_repo_id')->index();
            $table->string('name');
            $table->string('full_name');
            $table->string('html_url');
            $table->text('description')->nullable();
            $table->boolean('private');
            $table->string('default_branch')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('marketplace_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
