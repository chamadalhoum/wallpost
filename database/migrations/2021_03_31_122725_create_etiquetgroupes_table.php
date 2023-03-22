<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEtiquetgroupesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('etiquetgroupes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('state')->nullable();
            $table->unsignedBigInteger('groupe_id')->nullable();
            $table->foreign('groupe_id')->references('id')->on('groupes')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('etiquette_id')->nullable();
            $table->foreign('etiquette_id')->references('id')->on('etiquettes')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('etiquetgroupes');
    }
}
