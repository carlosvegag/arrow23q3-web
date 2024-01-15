<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('paypal_plan_id')->unique();
            $table->bigInteger('user_id')->unsigned();
            $table->integer('price');
            $table->datetime('date_started_at');
            $table->datetime('date_ends_on');
            $table->tinyInteger('auto_renewal');
            $table->datetime('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
