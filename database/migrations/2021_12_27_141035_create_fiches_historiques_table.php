<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFichesHistoriquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fiches_historiques', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('logo')->nullable();
            $table->text('description')->nullable();
            $table->text('locationName')->nullable();
            $table->text('state')->nullable();
            $table->string('name')->nullable();
            $table->string('placeId')->nullable();
            $table->text('url_map')->nullable();
            $table->integer('storeCode')->nullable();
            $table->date('closedatestrCode')->nullable();
            $table->string('primaryPhone')->nullable();
            $table->string('adwPhone')->nullable();
            $table->text('labels')->nullable();
            $table->string('additionalPhones')->nullable();
            $table->string('websiteUrl')->nullable();
            $table->boolean('etatwebsite', false)->nullable();
            $table->string('email')->nullable();
            $table->string('latitude', 100)->nullable();
            $table->string('longitude', 100)->nullable();
            $table->string('OpenInfo_status', 100)->nullable();
            $table->string('address', 100)->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postalCode', 6)->nullable();
            $table->date('OpenInfo_opening_date')->nullable();
            $table->string('OpenInfo_canreopen')->nullable();
             $table->string('methodverif')->nullable();
            $table->boolean('otheradress', false)->nullable();
            $table->unsignedBigInteger('franchises_id')->nullable();
            $table->foreign('franchises_id')->references('id')
                ->on('franchises')->onUpdate('cascade')
                ->onDelete('cascade');
                $table->unsignedBigInteger('fiche_id')->nullable();
            $table->foreign('fiche_id')->references('id')
                ->on('fiches')->onUpdate('cascade')
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
        Schema::dropIfExists('fiches_historiques');
    }
}
