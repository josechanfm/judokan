<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 比賽成績欄位。
     *
     * CompetitionApplication 的 fillable 與「賽事結果」頁面本來就有 result_rank / result_score，
     * 但資料表沒有這兩個欄位，所以成績存不進去（會出現 Unknown column 錯誤）。
     *
     * @return void
     */
    public function up()
    {
        Schema::table('competition_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('competition_applications', 'result_rank')) {
                $table->string('result_rank', 20)->nullable()->after('accepted');
            }
            if (! Schema::hasColumn('competition_applications', 'result_score')) {
                $table->integer('result_score')->nullable()->after('result_rank');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('competition_applications', function (Blueprint $table) {
            $table->dropColumn(['result_rank', 'result_score']);
        });
    }
};
