<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFicheusersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ficheusers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('fiche_id')->nullable();
            $table->foreign('fiche_id')->references('id')->on('fiches')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('franchise_id')->nullable();
            $table->foreign('franchise_id')->references('id')->on('franchises')->onUpdate('cascade')
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
        Schema::dropIfExists('ficheusers');
    }
}
