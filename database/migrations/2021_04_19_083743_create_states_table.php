<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->boolean('isGoogleUpdated')->default(0);
            $table->boolean('isDuplicate')->default(0);
            $table->boolean('isSuspended')->default(0);
            $table->boolean('canUpdate')->default(0);
            $table->boolean('canDelete')->default(0);
            $table->boolean('isVerified')->default(0);
            $table->boolean('needsReverification')->default(0);
            $table->boolean('isPendingReview')->default(0);
            $table->boolean('isDisabled')->default(0);
            $table->boolean('isPublished')->default(0);
            $table->boolean('isDisconnected')->default(0);
            $table->boolean('isLocalPostApiDisabled')->default(0);
            $table->boolean('canModifyServiceList')->default(0);
            $table->boolean('canHaveFoodMenus')->default(0);
            $table->boolean('hasPendingEdits')->default(0);
            $table->boolean('hasPendingVerification')->default(0);
            $table->boolean('canOperateHealthData')->default(0);
            $table->boolean('canOperateLodgingData')->default(0);
            $table->unsignedBigInteger('fiche_id')->default(0);
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
        Schema::dropIfExists('states');
    }
}
