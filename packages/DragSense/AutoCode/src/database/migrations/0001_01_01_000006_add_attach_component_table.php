<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up()
    {
        Schema::table('ac_components', function (Blueprint $table) {
            $table->string('attached')->nullable(); 
            $table->foreign('attached')
                ->references('_id')
                ->on('ac_collections');
        });
    }

    public function down()
    {
        Schema::table('ac_components', function (Blueprint $table) {
            $table->dropForeign(['attached']);
            $table->dropColumn('attached');
        });
    }
};
