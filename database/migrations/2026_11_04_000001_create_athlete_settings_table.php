<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 屬會的運動員設定：哪些會員是本館運動員、哪些是集訓隊成員。
     *
     * @return void
     */
    public function up()
    {
        Schema::create('athlete_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->index();
            $table->foreignId('member_id')->index();
            $table->boolean('is_club_athlete')->default(false);
            $table->boolean('is_team_member')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('athlete_settings');
    }
};
