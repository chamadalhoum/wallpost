<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatMetadatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metadatas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('metadatasId',45)->nullable();
            $table->string('locationName',45)->nullable();
            $table->string('placeId',45)->nullable();
            $table->string('access',45)->nullable();
            $table->string('type',45)->nullable();
            $table->text('mapsUrl')->nullable();
            $table->string('newReviewUrl',45)->nullable();
            $table->unsignedBigInteger('fiche_id')->nullable();
            $table->foreign('fiche_id')->references('id')->on('fiches')->onUpdate('cascade')
                ->onDelete('cascade');
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
        Schema::dropIfExists('metadatas');
    }
}
