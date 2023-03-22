<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFicheHourhistoriquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fiche_hourhistoriques', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('modif_type',45)->nullable();
            $table->string('state',45)->nullable();
            $table->text('old_content')->nullable();
            $table->text('new_content')->nullable();
            $table->unsignedBigInteger('fichehours_id');
            $table->foreign('fichehours_id')->references('id')->on('fichehours')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');

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
        Schema::dropIfExists('fiche_hourhistoriques');
    }
}
