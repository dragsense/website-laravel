<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('ac_pages', function (Blueprint $table) {
            $table->string('_id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('page');
            $table->string('layout'); 
            $table->foreign('layout')
                ->references('_id')
                ->on('ac_layouts');
            $table->json('scripts')->nullable();
            $table->string('url')->nullable();
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
            $table->json('setting')->default(json_encode([
                'title' => '',
                'desc' => '',
                'image' => null,
                'content' => '',
                'excerpt' => '',
                'status' => 'DRAFT',
            ]));
            $table->json('updater')->default(json_encode([]));
            $table->boolean('duplicated')->default(false);
            $table->json('creator')->default(json_encode([]));
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ac_pages');
    }
};
