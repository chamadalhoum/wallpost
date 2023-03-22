<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEtiquetgroupesfichesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       
        Schema::create('etiquetgroupesfiches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('state')->nullable();
        
            $table->unsignedBigInteger('etiquetgroupe_id')->nullable();
            $table->foreign('etiquetgroupe_id')->references('id')->on('etiquetgroupes')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('fiche_id')->nullable();
            $table->foreign('fiche_id')->references('id')->on('fiches')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('etiquetgroupesfiches');
    }
}
