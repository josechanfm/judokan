<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 集訓場次（一次集訓一筆，出席會員記在 training_camp_attendances）。
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_camps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->index();
            $table->string('title');
            $table->date('camp_date');
            $table->unsignedInteger('points')->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();

            // 同一屬會、同一日、同一名稱只會有一場集訓
            $table->unique(['organization_id', 'camp_date', 'title']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_camps');
    }
};
