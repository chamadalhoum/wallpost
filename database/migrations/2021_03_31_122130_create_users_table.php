<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('lastname', 45)->nullable();
            $table->string('firstname',45)->nullable();
            $table->date('datebirth')->nullable();
            $table->string('email',100)->nullable();
            $table->string('phone',12)->nullable();
            $table->string('sex',20)->nullable();
            $table->string('address',100)->nullable();
            $table->string('city',20)->nullable();
            $table->string('country',20)->nullable();
            $table->string('postalCode',6)->nullable();
            $table->string('username',100)->nullable();
            $table->string('password',65)->nullable();
            $table->string('type',20)->nullable();
            $table->string('state',20)->nullable();
            $table->text('photo')->nullable();
            $table->string('google_id')->nullable();
            $table->rememberToken();
            $table->unsignedBigInteger('franchises_id')->nullable();
            $table->foreign('franchises_id')->references('id')->on('franchises')->onDelete('cascade');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->foreign('role_id')->references('id')->on('roles')->onUpdate('cascade')
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
        Schema::dropIfExists('users');
    }
}
