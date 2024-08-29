<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('ac_layouts', function (Blueprint $table) {
            $table->string('_id')->primary();
            $table->string('name');
            $table->boolean('topComponent')->default(false);
            $table->boolean('bottomComponent')->default(false);
            $table->json('components')->default(json_encode([]));
            $table->json('updater')->default(json_encode([]));
            $table->json('creator')->default(json_encode([]));
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ac_layouts');
    }
};
