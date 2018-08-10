<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSongablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('songables', function (Blueprint $table) {
            $table->integer('song_id')->unsigned();
            $table->foreign('song_id')->references('id')->on('songs');
            $table->string('songable_type');
            $table->integer('songable_id');

            $table->string('owner_type', 255);
            $table->string('owner_description', 500)->nullable();
            $table->string('percentage', 20);
            $table->integer('signature_id')->nullable()->unsigned();
            $table->foreign('signature_id')->references('id')->on('signatures');
            $table->timestamp('signed_at')->nullable();

            // primary keys
            $table->primary(['song_id', 'songable_type', 'songable_id']);

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
        Schema::dropIfExists('songables');
    }
}
