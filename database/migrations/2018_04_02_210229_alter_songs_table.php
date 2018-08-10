<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSongsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->integer('attachment_id')->after('title')->unsigned()->nullable();
            $table->foreign('attachment_id')->references('attachment_id')->on('attachments');

            $table->integer('amendment_id')->after('attachment_id')->unsigned()->nullable();
            $table->foreign('amendment_id')->references('id')->on('amendments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('songs', function (Blueprint $table) {
            //
        });
    }
}
