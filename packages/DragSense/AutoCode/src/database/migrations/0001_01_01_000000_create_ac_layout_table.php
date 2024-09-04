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
            $table->string('main');
            $table->json('elements')->default(json_encode([
                "0" => [
                    "_uid" => "0",
                    "tagName" => "div",
                    "type" => "layout",
                    "layout" => "root",
                    "nodeValue" => "",
                    "childNodes" => []
                ]
            ]));
            $table->json('components')->default(json_encode([]));
            $table->json('forms')->default(json_encode([]));
            $table->json('_components')->default(json_encode([]));
            $table->json('_forms')->default(json_encode([]));
            $table->json('_elements')->default(json_encode([]));
            $table->json('_styles')->default(json_encode([]));
            $table->boolean('published')->default(true);
            $table->json('styles')->default(json_encode([]));
            $table->json('updater')->default(json_encode([]));
            $table->boolean('duplicated')->default(false);
            $table->json('creator')->default(json_encode([]));
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ac_layouts');
    }
};
