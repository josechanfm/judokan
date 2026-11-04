<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 每月運動員訓練記錄（中心/集訓、得勝、集訓節數、比賽日數）。
     *
     * 取代舊的「集訓場次 + 出席名單」設計。
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->index();
            $table->foreignId('member_id')->index();
            $table->date('month');
            $table->unsignedInteger('centre_count')->default(0);
            $table->unsignedInteger('win_count')->default(0);
            $table->unsignedInteger('session_count')->default(0);
            $table->unsignedInteger('competition_day_count')->default(0);
            $table->timestamps();

            // 每位運動員每個月只有一筆記錄
            $table->unique(['organization_id', 'member_id', 'month']);
        });

        Schema::dropIfExists('training_camp_attendances');
        Schema::dropIfExists('training_camps');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_records');
    }
};
