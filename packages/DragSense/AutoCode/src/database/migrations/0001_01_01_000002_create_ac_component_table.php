<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up()
    {
        Schema::create('ac_components', function (Blueprint $table) {
            $table->string('_id')->primary();
            $table->string('name');
            $table->string('type')->default('component');
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
            $table->json('_collStates')->default(json_encode([
                'limit' => 10,
                'page' => 1,
                'sort' => 'asc',
                'sortBy' => 'createdAt',
                'loadMore' => false,
                'withRelationship' => false,
                'docs' => [],
                'selectedRelationships' => [],
                'maxPage' => 0,
                'pagination' => ['leftRange' => 1, 'rightRange' => 1, 'nearRange' => 4, 'dots' => '...']
            ]));
            $table->json('collStates')->default(json_encode([]));
            $table->json('styles')->default(json_encode([]));
            $table->json('states')->default(json_encode([]));
            $table->json('events')->default(json_encode([]));
            $table->json('_events')->default(json_encode([]));
            $table->json('_states')->default(json_encode([]));
            $table->json('_components')->default(json_encode([]));
            $table->json('_forms')->default(json_encode([]));
            $table->json('_elements')->default(json_encode([]));
            $table->json('_styles')->default(json_encode([]));
            $table->boolean('published')->default(true);
            $table->json('updater')->default(json_encode([]));
            $table->boolean('duplicated')->default(false);
            $table->json('creator')->default(json_encode([]));
            $table->timestamps(); // This will create `created_at` and `updated_at` columns
        });
    }

   
    public function down()
    {
        Schema::dropIfExists('ac_components');
    }
};
