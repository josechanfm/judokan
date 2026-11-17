<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InternationalCategoryConfigSeeder extends Seeder
{
    /**
     * 國際賽組別設定：男子／女子 U10、U13、U15、U18。
     *
     * @return void
     */
    public function run()
    {
        // 可重複執行：先清掉舊的國際賽組別設定，再按最新定義寫入
        DB::table('configs')
            ->where('key', 'categories_weights')
            ->where('group_type', 'international')
            ->delete();

        $groups = [
            [
                'code' => 'intl_u10_male',
                'name' => '國際賽男童U10組',
                'description' => '小童組（U10），參加者年齡必須於8歲至9歲者',
                'age_range' => [8, 9],
                'male' => [
                    ['code' => '21-', 'name' => '-21Kg', 'limit' => [15, 21]],
                    ['code' => '23-', 'name' => '-23Kg', 'limit' => [22, 23]],
                    ['code' => '25-', 'name' => '-25Kg', 'limit' => [24, 25]],
                    ['code' => '27-', 'name' => '-27Kg', 'limit' => [26, 27]],
                    ['code' => '30-', 'name' => '-30Kg', 'limit' => [28, 30]],
                    ['code' => '33-', 'name' => '-33Kg', 'limit' => [31, 33]],
                    ['code' => '37-', 'name' => '-37Kg', 'limit' => [34, 37]],
                    ['code' => '41-', 'name' => '-41Kg', 'limit' => [38, 41]],
                    ['code' => '45-', 'name' => '-45Kg', 'limit' => [42, 45]],
                ],
                'female' => [],
            ],
            [
                'code' => 'intl_u10_female',
                'name' => '國際賽女童U10組',
                'description' => '小童組（U10），參加者年齡必須於8歲至9歲者',
                'age_range' => [8, 9],
                'male' => [],
                'female' => [
                    ['code' => '21-', 'name' => '-21Kg', 'limit' => [15, 21]],
                    ['code' => '23-', 'name' => '-23Kg', 'limit' => [22, 23]],
                    ['code' => '25-', 'name' => '-25Kg', 'limit' => [24, 25]],
                    ['code' => '27-', 'name' => '-27Kg', 'limit' => [26, 27]],
                    ['code' => '30-', 'name' => '-30Kg', 'limit' => [28, 30]],
                    ['code' => '33-', 'name' => '-33Kg', 'limit' => [31, 33]],
                    ['code' => '37-', 'name' => '-37Kg', 'limit' => [34, 37]],
                    ['code' => '41-', 'name' => '-41Kg', 'limit' => [38, 41]],
                    ['code' => '45-', 'name' => '-45Kg', 'limit' => [42, 45]],
                ],
            ],
            [
                'code' => 'intl_u13_male',
                'name' => '國際賽男童U13組',
                'description' => '兒童組（U13），參加者年齡必須於10歲至12歲者',
                'age_range' => [10, 12],
                'male' => [
                    ['code' => '24-', 'name' => '-24Kg', 'limit' => [15, 24]],
                    ['code' => '27-', 'name' => '-27Kg', 'limit' => [25, 27]],
                    ['code' => '30-', 'name' => '-30Kg', 'limit' => [28, 30]],
                    ['code' => '34-', 'name' => '-34Kg', 'limit' => [31, 34]],
                    ['code' => '38-', 'name' => '-38Kg', 'limit' => [35, 38]],
                    ['code' => '42-', 'name' => '-42Kg', 'limit' => [39, 42]],
                    ['code' => '46-', 'name' => '-46Kg', 'limit' => [43, 46]],
                    ['code' => '50-', 'name' => '-50Kg', 'limit' => [47, 50]],
                ],
                'female' => [],
            ],
            [
                'code' => 'intl_u13_female',
                'name' => '國際賽女童U13組',
                'description' => '兒童組（U13），參加者年齡必須於10歲至12歲者',
                'age_range' => [10, 12],
                'male' => [],
                'female' => [
                    ['code' => '22-', 'name' => '-22Kg', 'limit' => [15, 22]],
                    ['code' => '25-', 'name' => '-25Kg', 'limit' => [23, 25]],
                    ['code' => '28-', 'name' => '-28Kg', 'limit' => [26, 28]],
                    ['code' => '32-', 'name' => '-32Kg', 'limit' => [29, 32]],
                    ['code' => '36-', 'name' => '-36Kg', 'limit' => [33, 36]],
                    ['code' => '40-', 'name' => '-40Kg', 'limit' => [37, 40]],
                    ['code' => '44-', 'name' => '-44Kg', 'limit' => [41, 44]],
                    ['code' => '48-', 'name' => '-48Kg', 'limit' => [45, 48]],
                ],
            ],
            [
                'code' => 'intl_u15_male',
                'name' => '國際賽男子U15組',
                'description' => '少年組（U15），參加者年齡必須於13歲至14歲者',
                'age_range' => [13, 14],
                'male' => [
                    ['code' => '34-', 'name' => '-34Kg', 'limit' => [15, 34]],
                    ['code' => '38-', 'name' => '-38Kg', 'limit' => [35, 38]],
                    ['code' => '42-', 'name' => '-42Kg', 'limit' => [39, 42]],
                    ['code' => '46-', 'name' => '-46Kg', 'limit' => [43, 46]],
                    ['code' => '50-', 'name' => '-50Kg', 'limit' => [47, 50]],
                    ['code' => '55-', 'name' => '-55Kg', 'limit' => [51, 55]],
                    ['code' => '60-', 'name' => '-60Kg', 'limit' => [56, 60]],
                    ['code' => '66-', 'name' => '-66Kg', 'limit' => [61, 66]],
                ],
                'female' => [],
            ],
            [
                'code' => 'intl_u15_female',
                'name' => '國際賽女子U15組',
                'description' => '少年組（U15），參加者年齡必須於13歲至14歲者',
                'age_range' => [13, 14],
                'male' => [],
                'female' => [
                    ['code' => '32-', 'name' => '-32Kg', 'limit' => [15, 32]],
                    ['code' => '36-', 'name' => '-36Kg', 'limit' => [33, 36]],
                    ['code' => '40-', 'name' => '-40Kg', 'limit' => [37, 40]],
                    ['code' => '44-', 'name' => '-44Kg', 'limit' => [41, 44]],
                    ['code' => '48-', 'name' => '-48Kg', 'limit' => [45, 48]],
                    ['code' => '52-', 'name' => '-52Kg', 'limit' => [49, 52]],
                    ['code' => '57-', 'name' => '-57Kg', 'limit' => [53, 57]],
                    ['code' => '63-', 'name' => '-63Kg', 'limit' => [58, 63]],
                ],
            ],
            [
                'code' => 'intl_u18_male',
                'name' => '國際賽男子U18組',
                'description' => '青年組（U18），參加者年齡必須於15歲至17歲者',
                'age_range' => [15, 17],
                'male' => [
                    ['code' => '50-', 'name' => '-50Kg', 'limit' => [15, 50]],
                    ['code' => '55-', 'name' => '-55Kg', 'limit' => [51, 55]],
                    ['code' => '60-', 'name' => '-60Kg', 'limit' => [56, 60]],
                    ['code' => '66-', 'name' => '-66Kg', 'limit' => [61, 66]],
                    ['code' => '73-', 'name' => '-73Kg', 'limit' => [67, 73]],
                    ['code' => '81-', 'name' => '-81Kg', 'limit' => [74, 81]],
                    ['code' => '90-', 'name' => '-90Kg', 'limit' => [82, 90]],
                    ['code' => '90+', 'name' => '+90Kg', 'limit' => [91, 120]],
                ],
                'female' => [],
            ],
            [
                'code' => 'intl_u18_female',
                'name' => '國際賽女子U18組',
                'description' => '青年組（U18），參加者年齡必須於15歲至17歲者',
                'age_range' => [15, 17],
                'male' => [],
                'female' => [
                    ['code' => '40-', 'name' => '-40Kg', 'limit' => [15, 40]],
                    ['code' => '44-', 'name' => '-44Kg', 'limit' => [41, 44]],
                    ['code' => '48-', 'name' => '-48Kg', 'limit' => [45, 48]],
                    ['code' => '52-', 'name' => '-52Kg', 'limit' => [49, 52]],
                    ['code' => '57-', 'name' => '-57Kg', 'limit' => [53, 57]],
                    ['code' => '63-', 'name' => '-63Kg', 'limit' => [58, 63]],
                    ['code' => '70-', 'name' => '-70Kg', 'limit' => [64, 70]],
                    ['code' => '70+', 'name' => '+70Kg', 'limit' => [71, 100]],
                ],
            ],
        ];

        foreach ($groups as $group) {
            DB::table('configs')->insert([
                'organization_id' => 0,
                'key' => 'categories_weights',
                'group_type' => 'international',
                'value' => json_encode(
                    $group,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                ),
            ]);
        }
    }
}
