<?php
// Migration stub for builder_table_create_marketplace_packages.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('marketplace_packages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id')->unsigned();
            $table->string('name', 255);
            $table->string('description', 255);
            $table->string('code', 255);
            $table->text('image')->nullable();
            $table->string('author', 255);
            $table->boolean('needs_additional_processing')->default(0);
            $table->boolean('is_approved')->default(false);
            $table->text('repository_url')->nullable();
            $table->smallInteger('abandoned')->unsigned()->default(0);
            $table->bigInteger('git_stars')->nullable()->unsigned();
            $table->bigInteger('git_forks')->nullable()->unsigned();
            $table->bigInteger('downloads')->nullable()->unsigned();
            $table->bigInteger('favers')->nullable()->unsigned();
            $table->bigInteger('git_watchers')->nullable()->unsigned();
            $table->dateTime('last_updated_at')->nullable();
            $table->text('image')->nullable()->change();
            $table->text('packagist_url')->nullable();
            $table->bigInteger('latest_version_id')->nullable()->unsigned();
            $table->text('demo_url')->nullable();
            $table->text('product_url');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_packages');
    }
};
