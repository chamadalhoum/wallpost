<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceareashistoriquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('serviceareashistoriques', function (Blueprint $table) {
            $table->id();
            $table->string('state',45)->default('Inactif');
            $table->string("businessType",200)->nullable();
            $table->string("name",200)->nullable();
            $table->string("placeId",200)->nullable();
            $table->string("radiusKm",200)->nullable();
            $table->string("latitude",200)->nullable();
            $table->string("longitude",200)->nullable();
            $table->string("pays",200)->nullable();
            $table->string("zone",200)->nullable();
            $table->unsignedBigInteger('fiche_id')->nullable();
            $table->foreign('fiche_id')->references('id')->on('fiches')->onDelete('cascade');
            $table->unsignedBigInteger('serviceareas_id')->nullable();
            $table->foreign('serviceareas_id')->references('id')->on('serviceareas')->onDelete('cascade');
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
        Schema::dropIfExists('serviceareashistoriques');
    }
}
