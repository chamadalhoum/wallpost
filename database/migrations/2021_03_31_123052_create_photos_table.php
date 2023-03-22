<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('category', 45)->nullable();
            $table->string('name', 45)->unique();
            $table->integer('views')->nullable();
            $table->text('file')->nullable();
            $table->text('thumbnail')->nullable();
            $table->string('format', 45)->nullable();
            $table->string('width', 45)->nullable();
            $table->string('height', 45)->nullable();
            $table->string('profileName', 45)->nullable();
            $table->text('profilePhotoUrl')->nullable();
            $table->text('profileUrl')->nullable();
            $table->bool('avertir')->nullable();
            $table->text('messageAvertir')->nullable();
            $table->date('dateAvertir')->nullable();
            $table->unsignedBigInteger('userAvertir')->nullable();
            $table->bool('signials')->nullable();
            $table->date('signial_date')->nullable();
            $table->unsignedBigInteger('fiche_id')->nullable();

            $table->foreign('fiche_id')->references('id')->on('fiches')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('userAvertir')->references('id')->on('users')->onUpdate('cascade')
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
        Schema::dropIfExists('photos');
    }
}
