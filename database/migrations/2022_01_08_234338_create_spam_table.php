<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpamTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spam', function (Blueprint $table) {
            $table->id();            
            $table->char('t_acc_phone');
            $table->char('name');
            $table->char('peer');
            $table->text('text');
            $table->integer('delay');
            $table->tinyInteger('status');
            $table->timestamp('sent_at')->nullable();
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
        Schema::dropIfExists('spam');
    }
}
