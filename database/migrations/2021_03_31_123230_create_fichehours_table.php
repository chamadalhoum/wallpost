<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFichehoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fichehours', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type',45)->nullable();
            $table->string('open_date',45)->nullable();
            $table->string('close_date',45)->nullable();
            $table->string('open_time',45)->nullable();
            $table->string('close_time',45)->nullable();
            $table->date('specialhours_start_date')->nullable();
            $table->date('specialhours_end_date')->nullable();
            $table->string('specialhours_open_time',100)->nullable();
            $table->string('specialhours_close_time',100)->nullable();
            $table->text('new_content')->nullable();
            $table->unsignedBigInteger('fiche_id')->nullable();

            $table->foreign('fiche_id')->references('id')->on('fiches')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
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
        Schema::dropIfExists('fichehours');
    }
}
