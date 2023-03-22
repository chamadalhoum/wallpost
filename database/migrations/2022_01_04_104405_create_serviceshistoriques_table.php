<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceshistoriquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('serviceshistoriques', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('serviceId',45)->nullable();
            $table->string('displayName',45)->nullable();
            $table->text('description')->nullable();
            $table->float('prix')->nullable();
            $table->string('typeservice',45)->nullable();
            $table->string('state',45)->default('Inactif');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('categorie_id')->nullable();
            $table->foreign('categorie_id')->references('id')->on('categories')->onUpdate('cascade')
                ->onDelete('cascade');
                $table->unsignedBigInteger('service_id')->nullable();
            $table->foreign('service_id')->references('id')->on('services')->onUpdate('cascade')
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
        Schema::dropIfExists('serviceshistoriques');
    }
}
