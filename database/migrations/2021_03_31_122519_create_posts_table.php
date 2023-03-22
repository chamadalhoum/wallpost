<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string("genre",100)->nullable();
            $table->string("type",50)->nullable();
            $table->string("name",45)->nullable();
            $table->string("summary",45)->nullable();
            $table->string("topic_type",45)->nullable();
            $table->string("search_url",100)->nullable();
            $table->Date("event_start_date")->nullable();
            $table->Date("event_end_date")->nullable();
            $table->string("event_start_time",100)->nullable();
            $table->string("event_end_time",100)->nullable();
            $table->string("media_type",45)->nullable();
            $table->string("media_url",100)->nullable();
            $table->string("action_type",45)->nullable();
            $table->string("action_url",100)->nullable();
            $table->string("coupon_code",100)->nullable();
            $table->string("redeem_online_url",100)->nullable();
            $table->string("terms_conditions",100)->nullable();
            $table->tinyInteger('state')->nullable();

            $table->DateTime("programmed_date")->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger("fiche_id")->nullable();
            $table->foreign('fiche_id')->references('id')->on('fiches')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('posts');
    }
}
