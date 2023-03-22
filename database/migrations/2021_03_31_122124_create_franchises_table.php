<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFranchisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('franchises', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('socialReason', 45)->nullable();
            $table->string('state', 45)->nullable();
            $table->text('logo')->nullable();
            $table->string('type', 45)->nullable();
            $table->string('name', 45)->nullable();
            $table->string('taxRegistration',45)->nullable();
            $table->string('statutFiscale',45)->nullable();
            $table->string('tradeRegistry', 45)->nullable();
            $table->string('cinGerant', 45)->nullable();
            $table->string('fax', 45)->nullable();
            $table->string('phone', 45)->nullable();
            $table->string('email',45)->nullable();
            $table->string('address',45)->nullable();
            $table->string('postalCode', 45)->nullable();
            $table->string('city', 45)->nullable();
            $table->string('country',45)->nullable();
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
        Schema::dropIfExists('franchises');
    }
}
