<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_stats', function (Blueprint $table) {
            $table->id();
            $table->integer('localPostViewsSearch', 11)->default(0);
            $table->integer('localPostActions', 11)->default(0);
            $table->dateTime('date')->nullable();
            $table->unsignedBigInteger('post_fiche_id');
            $table->foreign('post_fiche_id')->references('id')->on('postfiches')->onUpdate('cascade')->onDelete('cascade');

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
        Schema::dropIfExists('post_stats');
    }
}
