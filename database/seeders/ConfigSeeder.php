<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       DB::table('configs')->insert([
        'organization_id'=>0,
        'key'=>'categories_weights',
        'value'=>'{
            "code": "public",
            "name": "公開組",
            "description": "參加者年齡必須於16歲或以上者",
            "age_range": [16, 99],
            "male": [{
                    "code": "55-",
                    "name": "-55Kg",
                    "limit": [45, 55]
                },{
                    "code": "60-",
                    "name": "-60Kg",
                    "limit": [55, 60]
                },{
                    "code": "66-",
                    "name": "-66Kg",
                    "limit": [61, 66]
                }, {
                    "code": "73-",
                    "name": "-73Kg",
                    "limit": [67, 73]
                }, {
                    "code": "81-",
                    "name": "-81Kg",
                    "limit": [74, 81]
                }, {
                    "code": "90-",
                    "name": "-90Kg",
                    "limit": [82, 90]
                }, {
                    "code": "OPEN",
                    "name": "Open",
                    "limit": [50, 120]
                }
            ],
            "female": [{
                    "code": "44-",
                    "name": "-44Kg",
                    "limit": [34, 44]
                },{
                    "code": "48-",
                    "name": "-48Kg",
                    "limit": [44, 48]
                }, {
                    "code": "52-",
                    "name": "-52Kg",
                    "limit": [49, 52]
                }, {
                    "code": "57-",
                    "name": "-57Kg",
                    "limit": [53, 57]
                }, {
                    "code": "63-",
                    "name": "-63Kg",
                    "limit": [58, 63]
                }, {
                    "code": "70-",
                    "name": "-70Kg",
                    "limit": [64, 70]
                }, {
                    "code": "OPEN",
                    "name": "Open",
                    "limit": [30, 120]
                }
            ]
        }',
    ]);

    DB::table('configs')->insert([
        'organization_id'=>0,
        'key'=>'categories_weights',
        'value'=>'{
            "code":"junior_a",
            "name": "少年A組",
            "description": "參加者年齡必須於15歲至17歲或以上者",
            "age_range": [15, 17],
            "male": [{
                    "code": "45-",
                    "name": "-45Kg",
                    "limit": [35, 45]
                },{
                    "code": "50-",
                    "name": "-50Kg",
                    "limit": [46, 50]
                }, {
                    "code": "55-",
                    "name": "-55Kg",
                    "limit": [51, 55]
                }, {
                    "code": "60-",
                    "name": "-60Kg",
                    "limit": [56, 60]
                }, {
                    "code": "66-",
                    "name": "-66Kg",
                    "limit": [61, 66]
                }, {
                    "code": "73-",
                    "name": "-73Kg",
                    "limit": [67, 73]
                }, {
                    "code": "73+",
                    "name": "+73Kg",
                    "limit": [73, 83]
                }
            ],
            "female": [{
                    "code": "44-",
                    "name": "-44Kg",
                    "limit": [34, 44]
                }, {
                    "code": "48-",
                    "name": "-48Kg",
                    "limit": [45, 48]
                }, {
                    "code": "52-",
                    "name": "-52Kg",
                    "limit": [49, 52]
                }, {
                    "code": "57-",
                    "name": "-57Kg",
                    "limit": [53, 57]
                }, {
                    "code": "63-",
                    "name": "-63Kg",
                    "limit": [58, 63]
                }, {
                    "code": "70-",
                    "name": "-70Kg",
                    "limit": [64, 70]
                }, {
                    "code": "70+",
                    "name": "+70Kg",
                    "limit": [70, 90]
                }
            ]
        }',
    ]);

    DB::table('configs')->insert([
        'organization_id'=>0,
        'key'=>'categories_weights',
        'value'=>'{
            "code": "junior_b",
            "name": "少年B組",
            "description": "參加者年齡必須於13歲至14歲或以上者",
            "age_range": [13, 14],
            "male": [{
                    "code": "38-",
                    "name": "-60Kg",
                    "limit": [28, 38]
                },{
                    "code": "42-",
                    "name": "-42Kg",
                    "limit": [38, 42]
                },{
                    "code": "46-",
                    "name": "-46Kg",
                    "limit": [43, 46]
                }, {
                    "code": "50-",
                    "name": "-50Kg",
                    "limit": [47, 50]
                }, {
                    "code": "55-",
                    "name": "-55Kg",
                    "limit": [51, 55]
                }, {
                    "code": "60-",
                    "name": "-60Kg",
                    "limit": [56, 60]
                }, {
                    "code": "66-",
                    "name": "-66Kg",
                    "limit": [61, 66]
                }, {
                    "code": "66+",
                    "name": "+66Kg",
                    "limit": [66, 86]
                }
            ],
            "female": [{
                    "code": "36-",
                    "name": "-36Kg",
                    "limit": [26, 36]
                },{
                    "code": "40-",
                    "name": "-40Kg",
                    "limit": [36, 40]
                }, {
                    "code": "44-",
                    "name": "-44Kg",
                    "limit": [41, 44]
                }, {
                    "code": "48-",
                    "name": "-48Kg",
                    "limit": [45, 48]
                }, {
                    "code": "52-",
                    "name": "-52Kg",
                    "limit": [49, 52]
                }, {
                    "code": "57-",
                    "name": "-57Kg",
                    "limit": [53, 57]
                }, {
                    "code": "63-",
                    "name": "-63Kg",
                    "limit": [58, 63]
                }, {
                    "code": "63+",
                    "name": "+63Kg",
                    "limit": [63, 83]
                }
            ]
        }',
    ]);

    DB::table('configs')->insert([
        'organization_id'=>0,
        'key'=>'categories_weights',
        'value'=>'{
            "code": "child_c",
            "name": "兒童C組",
            "description": "參加者年齡必須於11歲至12歲或以上者",
            "age_range": [11, 12],
            "male": [{
                    "code": "27-",
                    "name": "-27Kg",
                    "limit": [17, 27]
                },{
                    "code": "30-",
                    "name": "-30Kg",
                    "limit": [28, 30]
                }, {
                    "code": "34-",
                    "name": "-34Kg",
                    "limit": [31, 34]
                }, {
                    "code": "38-",
                    "name": "-38Kg",
                    "limit": [35, 38]
                }, {
                    "code": "42-",
                    "name": "-42Kg",
                    "limit": [39, 42]
                }, {
                    "code": "46-",
                    "name": "-46Kg",
                    "limit": [43, 46]
                }, {
                    "code": "50-",
                    "name": "-50Kg",
                    "limit": [47, 50]
                }, {
                    "code": "55-",
                    "name": "-55Kg",
                    "limit": [51, 55]
                }, {
                    "code": "60-",
                    "name": "-60Kg",
                    "limit": [56, 60]
                }, {
                    "code": "60+",
                    "name": "+60Kg",
                    "limit": [60, 70]
                }
            ],
            "female": [{
                    "code": "25-",
                    "name": "-25Kg",
                    "limit": [15, 25]
                }, {
                    "code": "28-",
                    "name": "-28Kg",
                    "limit": [26, 28]
                }, {
                    "code": "32-",
                    "name": "-32Kg",
                    "limit": [29, 32]
                }, {
                    "code": "36-",
                    "name": "-36Kg",
                    "limit": [33, 36]
                }, {
                    "code": "40-",
                    "name": "-40Kg",
                    "limit": [37, 40]
                }, {
                    "code": "44-",
                    "name": "-44Kg",
                    "limit": [41, 44]
                }, {
                    "code": "48-",
                    "name": "-48Kg",
                    "limit": [45, 48]
                }, {
                    "code": "52-",
                    "name": "-52Kg",
                    "limit": [49, 52]
                }, {
                    "code": "57-",
                    "name": "-57Kg",
                    "limit": [53, 57]
                }, {
                    "code": "57+",
                    "name": "+57Kg",
                    "limit": [57, 67]
                }
            ]
        }',
    ]);

    DB::table('configs')->insert([
        'organization_id' => 0,
        'key' => 'categories_weights',
        'value' => '{
            "code": "child_d",
            "name": "兒童D組",
            "description": "參加者年齡必須於9歲至10歲者",
            "age_range": [9, 10],
            "male": [
                {"code": "24-", "name": "-24Kg", "limit": [15, 24]},
                {"code": "27-", "name": "-27Kg", "limit": [25, 27]},
                {"code": "30-", "name": "-30Kg", "limit": [28, 30]},
                {"code": "34-", "name": "-34Kg", "limit": [31, 34]},
                {"code": "38-", "name": "-38Kg", "limit": [35, 38]},
                {"code": "42-", "name": "-42Kg", "limit": [39, 42]},
                {"code": "46-", "name": "-46Kg", "limit": [43, 46]},
                {"code": "46+", "name": "+46Kg", "limit": [47, 56]}
            ],
            "female": [
                {"code": "22-", "name": "-22Kg", "limit": [15, 22]},
                {"code": "25-", "name": "-25Kg", "limit": [23, 25]},
                {"code": "28-", "name": "-28Kg", "limit": [26, 28]},
                {"code": "32-", "name": "-32Kg", "limit": [29, 32]},
                {"code": "36-", "name": "-36Kg", "limit": [33, 36]},
                {"code": "40-", "name": "-40Kg", "limit": [37, 40]},
                {"code": "44-", "name": "-44Kg", "limit": [41, 44]},
                {"code": "44+", "name": "+44Kg", "limit": [44, 54]}
            ]
        }'
    ]);

    DB::table('configs')->insert([
        'organization_id' => 0,
        'key' => 'categories_weights',
        'value' => '{
            "code": "child_e",
            "name": "兒童E組",
            "description": "參加者年齡必須於7歲至8歲者",
            "age_range": [7, 8],
            "male": [
                {"code": "21-", "name": "-21Kg", "limit": [15, 21]},
                {"code": "23-", "name": "-23Kg", "limit": [22, 23]},
                {"code": "25-", "name": "-25Kg", "limit": [24, 25]},
                {"code": "27-", "name": "-27Kg", "limit": [26, 27]},
                {"code": "30-", "name": "-30Kg", "limit": [28, 30]},
                {"code": "33-", "name": "-33Kg", "limit": [31, 33]},
                {"code": "33+", "name": "+33Kg", "limit": [34, 43]}
            ],
            "female": [
                {"code": "21-", "name": "-21Kg", "limit": [15, 21]},
                {"code": "23-", "name": "-23Kg", "limit": [22, 23]},
                {"code": "25-", "name": "-25Kg", "limit": [24, 25]},
                {"code": "27-", "name": "-27Kg", "limit": [26, 27]},
                {"code": "30-", "name": "-30Kg", "limit": [28, 30]},
                {"code": "33-", "name": "-33Kg", "limit": [31, 33]},
                {"code": "33+", "name": "+33Kg", "limit": [33, 43]}
            ]
        }'
    ]);

        DB::table('configs')->insert([
            'organization_id'=>0,
            'key'=>'competition_roles',
            'value'=>'[
                {
                    "value": "athlete",
                    "label": "運動員"
                },{
                    "value": "referee",
                    "label": "裁判"
                },{
                    "value": "coach",
                    "label": "教練"
                },{
                    "value": "staff",
                    "label": "工作人員"
                }
            ]',
        ]);
        DB::table('configs')->insert([
            'organization_id'=>0,
            'key'=>'article_categories',
            'value'=>'[{"value":"BLOG","label":"Blog"},{"value":"NEWS","label":"News"}]',
        ]);
        DB::table('configs')->insert([
            'organization_id'=>0,
            'key'=>'bulletin_categories',
            'value'=>'[{"value": "CMTE","label": "Committee"}, {"value": "GEN","label": "General"}]',
        ]);
        DB::table('configs')->insert([
            'organization_id'=>0,
            'key'=>'message_categories',
            'value'=>'[{
                "value": "ALL",
                "label": "Everyone"
            }, {
                "value": "ORG",
                "label": "Organization"
            }, {
                "value": "MEM",
                "label": "Member Only"
            }, {
                "value": "IND",
                "label": "Individual"
            }]',
        ]);
        DB::table('configs')->insert([
            'organization_id'=>0,
            'key'=>'event_categories',
            'value'=>'[{
                "value": "TRANING",
                "label": "Traning"
            }, {
                "value": "COURSE",
                "label": "Course"
            }]',
        ]);
        DB::table('configs')->insert([
            'organization_id'=>0,
            'key'=>'certificate_categories',
            'value'=>'[{
                "value": "BELT",
                "label": "Belt"
            }, {
                "value": "REFEREE",
                "label": "Referee"
            }, {
                "value": "COACH",
                "label": "Coach"
            }, {
                "value": "OTHER",
                "label": "Other"
            }]',
        ]);
        DB::table('configs')->insert([
            'organization_id'=>0,
            'key'=>'attendance_status',
            'value'=>'[{
                    "value": "ATTEND",
                    "label": "Attend"
                }, {
                    "value": "LATE",
                    "label": "Late"
                }, {
                    "value": "EXCUSE",
                    "label": "Excuse"
                }, {
                    "value": "ABSENT",
                    "label": "Absent"
            }]'
        ]);
        DB::table('configs')->insert([
            'organization_id'=>0,
            'key'=>'card_style',
            'value'=>'{
                "card_01": {
                    "background": "card_01.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:#c24641"
                },
                "card_02": {
                    "background": "card_02.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                },
                "card_03": {
                    "background": "card_03.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                },
                "card_04": {
                    "background": "card_04.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                },
                "card_05": {
                    "background": "card_05.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                },
                "card_06": {
                    "background": "card_06.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                },
                "card_07": {
                    "background": "card_07.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:#c24641"
                },
                "card_08": {
                    "background": "card_08.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:#c24641"
                },
                "card_09": {
                    "background": "card_09.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                },
                "card_10": {
                    "background": "card_10.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                },
                "card_11": {
                    "background": "card_11.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                },
                "card_12": {
                    "background": "card_12.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                },
                "card_13": {
                    "background": "card_13.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:#c24641"
                },
                "card_14": {
                    "background": "card_14.png",
                    "logo": "site_logo.png",
                    "font_style": "font-family:sans-serif;color:white"
                }
            }'
            ]);

            DB::table('configs')->insert([
                'organization_id'=>0,
                'key'=>'belt_ranks',
                'value'=>'[
                    {"rankCode":"white","name_zh":"白帶","name_en":"White Belt (Dan )"},
                    {"rankCode":"yellow","name_zh":"黃帶","name_en":"Yellow Belt"},
                    {"rankCode":"orange","name_zh":"橙帶","name_en":"Orange Belt"},
                    {"rankCode":"green","name_zh":"綠帶","name_en":"Green Belt"},
                    {"rankCode":"blue","name_zh":"藍帶","name_en":"Blue Belt"},
                    {"rankCode":"brown","name_zh":"啡帶","name_en":"Brown Belt"},
                    {"rankCode":"dan_1","name_zh":"黑帶初段","name_en":"Blank Belt (Dan 1)"},
                    {"rankCode":"dan_2","name_zh":"黑帶2段","name_en":"Blank Belt (Dan 2)"},
                    {"rankCode":"dan_3","name_zh":"黑帶3段","name_en":"Blank Belt (Dan 3)"},
                    {"rankCode":"dan_4","name_zh":"黑帶4段","name_en":"Blank Belt (Dan 4)"},
                    {"rankCode":"dan_5","name_zh":"黑帶5段","name_en":"Blank Belt (Dan 5)"},
                    {"rankCode":"dan_6","name_zh":"黑帶6段","name_en":"Blank Belt (Dan 6)"},
                    {"rankCode":"dan_7","name_zh":"黑帶7段","name_en":"Blank Belt (Dan 7)"},
                    {"rankCode":"dan_8","name_zh":"黑帶8段","name_en":"Blank Belt (Dan 8)"},
                    {"rankCode":"dan_9","name_zh":"黑帶9段","name_en":"Blank Belt (Dan 9)"},
                    {"rankCode":"dan_10","name_zh":"黑帶10段","name_en":"Blank Belt (Dan 10)"}
                    ]'
            ]);
            DB::table('configs')->insert([
                'organization_id'=>0,
                'key'=>'staff_options',
                'value'=>'[{"value":"parent","label":"家長義工"},{"value":"student","label":"學生義工"},{"value":"weighting","label":"過磅工作人員(裁判人員或已有相關工作經驗優先)"},{"value":"mc_match","label":"賽事司儀(已有相關工作經驗優先)"},{"value":"checker","label":"檢錄組(已有相關工作經驗優先)"},{"value":"score_digital","label":"電子計分操作員(已持有IJF國際柔道賽事計時記分系統操作員培訓優先報名)"},{"value":"score_manual","label":"計時記分操作員(手動計時記分)"},{"value":"control","label":"司令台(已有相關工作經驗優先)"},{"value":"mc_award","label":"頒獎禮司儀(已有相關工作經驗優先)"},{"value":"helper_award","label":"頒獎小組(已有相關工作經驗優先)"},{"value":"general","label":"總務小組"},{"value":"venue_setup","label":"場地佈置"}]'
            ]);
            DB::table('configs')->insert([
                'organization_id'=>0,
                'key'=>'referee_options',
                'value'=>'[{"value": "International_A","label": "已持有國際A級裁判資格"},{"value": "International_B","label": "已持有國際B級裁判資格"},{"value": "Local_A","label": "已持有本地裁判A級資格"},{"value": "Local_B","label": "已持有本地裁判B級資格"},{"value": "Other_Country","label": "其他地區裁判資格"},{"value": "Trainee","label": "實習裁判"}]'
            ]);
            DB::table('configs')->insert([
                'organization_id'=>0,
                'key'=>'competition_score_categories',
                'value'=>'[
                    {"value":"WORLD","label":"世界賽"},
                    {"value":"ASIA","label":"亞洲賽"},
                    {"value":"INTLP","label":"國際公開賽"},
                    {"value":"ACUP","label":"亞洲盃"},
                    {"value":"CHINA","label":"中國"},
                    {"value":"IINVT","label":"國際邀請賽"},
                    {"value":"EASIA","label":"東亞"},
                    {"value":"INTPT","label":"埠際賽"},
                    {"value":"LOCAL","label":"本地"}
                ]'
            ]);
            DB::table('configs')->insert([
                'organization_id'=>0,
                'key'=>'member_tiers',
                'value'=>'[
                    {"value":"TEAM_A","label":"代表隊A"},
                    {"value":"TEAM_B","label":"代表隊B"}
                ]'
            ]);
            DB::table('configs')->insert([
                'organization_id'=>0,
                'key'=>'competition_results',
                'value'=>'[
                    {"value": "first","label": "第一名","label_zh":"第一名","label_en":"First Place"},
                    {"value": "second","label": "第二名","label_zh":"第二名","label_en":"Second Place"},
                    {"value": "third","label": "第三名","label_zh":"第三名","label_en":"Third Place"},
                    {"value": "fifth","label": "第五名","label_zh":"第五名","label_en":"Fifth Place"},
                    {"value": "seventh","label": "第七名","label_zh":"第七名","label_en":"Senventh Place"},
                    {"value": "advance","label": "晉級","label_zh":"晉級","label_en":"Advance"},
                    {"value": "participate","label": "出席","label_zh":"出席","label_en":"Participate"}
                ]'
            ]);
            DB::table('configs')->insert([
                'organization_id'=>0,
                'key'=>'regions',
                'value'=>'[
                    {"value":"C","label":"Central Zone"},
                    {"value":"S","label":"South Zone"},
                    {"value":"E","label":"East Zone"},
                    {"value":"W","label":"West Zone"},
                    {"value":"SE","label":"South East Zone"}
                ]'
            ]);
    
    }
}
