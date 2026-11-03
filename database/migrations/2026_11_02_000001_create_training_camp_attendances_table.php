<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 集訓出席記錄（哪一場集訓有哪些會員出席）。
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_camp_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_camp_id')->constrained('training_camps')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['training_camp_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_camp_attendances');
    }
};
