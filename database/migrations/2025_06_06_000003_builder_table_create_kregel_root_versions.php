<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('marketplace_versions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->foreignIdFor(\App\Models\Package::class);
            $table->string('semantic_version', 255);

            $table->unique(['package_id', 'semantic_version'], 'unique_package_version');

            $table->string('hash', 255);
            $table->string('license', 255);
            $table->json('requires');
            $table->json('requires_dev');
            $table->json('suggests');
            $table->json('provides');
            $table->json('conflicts');
            $table->json('replaces');
            $table->json('tags');
            $table->text('installation_commands');
            $table->text('description');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_versions');
    }
};

