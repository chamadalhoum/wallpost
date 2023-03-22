<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategorieshistoriquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categorieshistoriques', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('categorieId',45)->nullable();
            $table->string('displayName',255)->nullable();
            $table->string('type',255)->nullable();
            $table->string('state',45)->default('Inactif');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('fiche_id')->nullable();
            $table->foreign('fiche_id')->references('id')->on('fiches')->onUpdate('cascade')
                ->onDelete('cascade');
                $table->unsignedBigInteger('categorie_id')->nullable();
                $table->foreign('categorie_id')->references('id')->on('fiches')->onUpdate('cascade')
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
        Schema::dropIfExists('categorieshistoriques');
    }
}
