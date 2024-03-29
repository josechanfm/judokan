<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('abbr');
            $table->string('territory')->nullable();
            $table->string('title')->nullable();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('href')->nullable();
            $table->string('avatar')->nullable();
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->string('registration_code')->nullable();
            $table->string('president')->nullable();
            $table->boolean('status')->nullable();
            $table->string('card_style')->nullable();
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
        Schema::dropIfExists('organizations');
    }
};
