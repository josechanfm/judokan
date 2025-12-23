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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('name_zh')->nullable();
            $table->string('name_fn')->nullable();
            $table->string('display_name')->nullable();
            $table->char('gender',1)->nullable();
            $table->date('dob')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('country')->nullable();
            $table->string('nationality')->nullable();
            $table->string('address')->nullable();
            $table->string('positions')->nullable();
            $table->string('avatar')->nullable();
            
            // 會員有效期管理（保留原字段）
            $table->date('valid_at')->nullable();
            $table->date('expired_at')->nullable();
            
            // 新增字段
            $table->string('membership_number')->unique()->nullable(); // 會員編號
            $table->string('id_card_number')->unique()->nullable(); // 身份證號碼/護照號碼
            $table->string('occupation')->nullable(); // 職業
            $table->year('exam_year')->nullable(); // 最近考試年份
            $table->string('belt')->nullable(); // 帶別
            $table->string('license_number')->nullable(); // 教練/裁判證號碼
            $table->enum('membership_status', ['active', 'inactive', 'suspended', 'expired'])->default('active');
            
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
        Schema::dropIfExists('members');
    }
};
