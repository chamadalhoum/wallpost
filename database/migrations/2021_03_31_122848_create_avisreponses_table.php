<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvisreponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avisreponses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('reponse')->nullable();
            $table->boolean('CreateOrUpadate')->default(0);
            $table->unsignedBigInteger('avis_id')->nullable();

            $table->foreign('avis_id')->references('id')->on('avis')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')
                ->onDelete('cascade');
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
        Schema::dropIfExists('avisreponses');
    }
}
