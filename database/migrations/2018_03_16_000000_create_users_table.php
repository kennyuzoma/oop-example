<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->increments('id');
            $table->string('uuid')->nullable()->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->string('stage_name', 255)->nullable();
            $table->integer('pro_id')->nullable()->unsigned();
            $table->foreign('pro_id')->references('id')->on('pros');
            $table->integer('publisher_id')->nullable()->unsigned();
            $table->foreign('publisher_id')->references('id')->on('publishers');
            $table->string('ipi_cae', 40)->unique()->nullable();
            $table->integer('signature_id')->nullable()->unsigned();
            $table->string('address', 255)->nullable();
            $table->string('address2', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('zip', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('active_plan')->default('free');
            $table->string('email_subscriber_id')->nullable();
            $table->json('settings')->nullable();
            $table->json('created_by')->nullable();
            $table->string('code')->nullable();
            $table->integer('status')->nullable()->unsigned();

            $table->rememberToken();
            
            $table->softDeletes();
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
