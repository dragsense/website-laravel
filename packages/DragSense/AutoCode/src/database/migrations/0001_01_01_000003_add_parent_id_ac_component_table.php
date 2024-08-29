<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
   
    public function up()
    {
        Schema::table('ac_components', function (Blueprint $table) {
            $table->string('parent')->nullable(); 
            $table->foreign('parent')
                ->references('_id')
                ->on('ac_components');
        });
    }

  
    public function down()
    {
        Schema::table('ac_components', function (Blueprint $table) {
            $table->dropForeign(['parent']);
            $table->dropColumn('parent');
        });
    }
};
