<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatistiquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statistiques', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('metricUnspecified', 45)->nullable();
            $table->string('all', 45)->nullable();
            $table->integer('queriesDirect')->nullable();
            $table->integer('queriesIndirect')->nullable();
            $table->integer('queriesChain')->nullable();
            $table->integer('viewsMaps')->nullable();
            $table->integer('viewsSearch')->nullable();
            $table->integer('actionsWebsite')->nullable();
            $table->integer('actionsPhone')->nullable();
            $table->integer('actionsDrivingDirections')->nullable();
            $table->integer('photosViewsMerchant')->nullable();
            $table->integer('photosViewsCustomers')->nullable();
            $table->dateTime('statistiques_daily')->nullable();
            $table->integer('photosCountMerchant')->nullable();
            $table->integer('photosCountCustomers')->nullable();
            $table->integer('localPostViewsSearch')->nullable();
            $table->dateTime('date')->nullable();
            $table->integer('localPostActions')->nullable();
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
        Schema::dropIfExists('statistiques');
    }
}
