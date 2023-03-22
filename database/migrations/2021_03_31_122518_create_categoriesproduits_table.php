<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesproduitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categoriesproduits', function (Blueprint $table) {
            $table->id();
            $table->string('displayName',255)->nullable();
            $table->unsignedBigInteger('fiche_id')->nullable();
            $table->foreign('fiche_id')->references('id')->on('fiches')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('post_id')->nullable();
            $table->foreign('post_id')->references('id')->on('posts')->onUpdate('cascade')
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
        Schema::dropIfExists('categoriesproduits');
    }
}
