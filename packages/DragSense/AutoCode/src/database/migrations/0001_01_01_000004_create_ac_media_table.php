<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('ac_media', function (Blueprint $table) {
            $table->string('_id')->primary();
            $table->string('src')->nullable();
            $table->string('name')->nullable(false);
            $table->string('fileName')->nullable();
            $table->string('type')->nullable();
            $table->string('alt')->default('')->nullable(false);
            $table->unsignedBigInteger('size')->default(0);
            $table->json('dimensions')->nullable();
            $table->string('mimetype')->default('');
            $table->timestamps();
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('ac_media');
    }
};
