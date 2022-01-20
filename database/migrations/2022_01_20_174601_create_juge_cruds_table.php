<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJugeCRUDSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('juge_cruds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('name', 50);
            $table->char('model', 50);
            $table->bigInteger('user_id')->unsigned();
            $table->char('type', 50)->nullable();
            $table->smallInteger('position')->unsigned()->nullable();
            $table->boolean('active')->default(1);
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
        Schema::dropIfExists('juge_cruds');
    }
}
