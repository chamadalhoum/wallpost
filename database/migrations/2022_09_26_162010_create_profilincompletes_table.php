<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilincompletesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profilincompletes', function (Blueprint $table) {
            $table->id();
            $table->bigIncrements('id');
            $table->boolean('storeCode')->nullable();
            $table->boolean('description')->nullable();
            $table->boolean('websiteUrl')->nullable();
            $table->boolean('adwPhone')->nullable();
            $table->boolean('locationName')->nullable();
            $table->boolean('regularHours')->nullable();
            $table->boolean('serviceArea')->nullable();
            $table->boolean('Service')->nullable();
            $table->boolean('attributes')->nullable();
            $table->boolean('moreHours')->nullable();
            $table->integer('total')->nullable();
            $table->double('totalfiche')->nullable();
            $table->boolean('Photo')->nullable();
            $table->boolean('Post')->nullable();
            $table->string('title')->nullable();
            $table->string('etat')->nullable();
            $table->unsignedBigInteger('fiche_id')->nullable();
        
         
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
        Schema::dropIfExists('profilincompletes');
    }
}
