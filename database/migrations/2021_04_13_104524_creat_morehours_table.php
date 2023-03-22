<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatMorehoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('morehours', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('morehoursId',45)->nullable();
            $table->string('displayName',45)->nullable();
            $table->text('localized')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('categorie_id')->nullable();
            $table->foreign('categorie_id')->references('id')->on('categories')->onUpdate('cascade')
                ->onDelete('cascade');
                $table->unsignedBigInteger('fiche_id')->nullable();
                $table->foreign('fiche_id')->references('id')->on('fiches')->onUpdate('cascade')
                ->onDelete('cascade');
                $table->string('openDay',45)->nullable();
                $table->time('openTime')->nullable();
                $table->string('closeDay',45)->nullable();
                $table->time('closeTime')->nullable();
                $table->string('type',45)->nullable();
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
        Schema::dropIfExists('morehours');
    }
}
