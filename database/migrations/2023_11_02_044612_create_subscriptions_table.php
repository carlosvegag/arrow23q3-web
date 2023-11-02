<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            // Estructura de la base de datos
            $table->id('idsuscriptions');
            $table->unsignedBigInteger('user_id');
            $table->integer('precio');
            $table->dateTime('date_started_at');
            $table->dateTime('date_ends_on');
            $table->tinyInteger('renewal');
            $table->dateTime('finish_at');
            $table->dateTime('renewed_cancelled_at');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
