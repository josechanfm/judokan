<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\CompetitionScore;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$commit = in_array('--commit', $argv, true);
$organizationId = 1;
$path = 'D:\judo\澳門柔道運動員(資料庫)20250226.xlsm';

// --reset：先刪掉上一次匯入產生的比賽與報名（保留比賽 1 = 使用者自己的比賽）
if (in_array('--reset', $argv, true)) {
    $ids = Competition::where('organization_id', $organizationId)->where('id', '<>', 1)->pluck('id');
    $apps = CompetitionApplication::whereIn('competition_id', $ids)->count();
    CompetitionApplication::whereIn('competition_id', $ids)->delete();
    Competition::whereIn('id', $ids)->delete();
    echo 'reset: removed competitions='.$ids->count().' applications='.$apps."\n";
    echo 'remaining competitions='.Competition::count().' scored applications='.CompetitionApplication::whereNotNull('result_score')->count()."\n";
    exit;
}

// 性質 → 積分表（competition_scores.title）
$schemeByNature = [
    '亞運' => '亞洲運動會-世界排名積分',
    '亞洲' => '亞洲柔道錦標賽-世界排名積分',
    '亞洲賽' => '亞洲柔道錦標賽-世界排名積分',
    '亞青' => '亞洲青少年錦標賽-世界排名積分',
    '世大' => '世界大學生運動會',
    '東亞' => '東亞柔道錦標賽',
    '東亞運' => '東亞柔道錦標賽',
    '全國賽' => '全國錦標賽',
    '泛太平洋' => '國際柔道邀請賽-世界排名積分',
    '國際' => '國際柔道邀請賽',
    '埠際' => '大灣區柔道邀請賽',
    '四地賽' => '大灣區柔道邀請賽',
    '地區賽' => '大灣區柔道邀請賽',
    '交流' => '大灣區柔道邀請賽',
    '學界' => '學界柔道比賽-三級賽事',
    '計分賽' => '全澳柔道計分賽-二級賽事',
    '全澳賽' => '全澳柔道公開賽-一級賽事',
    '公開賽' => '全澳柔道公開賽-一級賽事',
    '本地' => '全澳柔道計分賽-二級賽事',
];

$schemes = CompetitionScore::all()->keyBy('title');

// 職位 → role
$roleMap = [
    '運動員' => 'athlete', '裁判' => 'referee', '教練' => 'coach', '領隊' => 'staff',
    '副領隊' => 'staff', '工作人員' => 'staff',
];

$reader = IOFactory::createReaderForFile($path);
$reader->setReadDataOnly(true);
$sheet = $reader->load($path)->getSheetByName('2.比賽成績');
$cell = function ($col, $row) use ($sheet) {
    $value = trim((string) $sheet->getCell($col.$row)->getValue());

    // 資料庫欄位是 utf8(3 bytes)，先移除 4 bytes 的罕用字（CJK 擴充 B 以後）避免寫入失敗
    return preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value);
};

$resultMap = [
    '第一名' => 'first', '冠軍' => 'first',
    '第二名' => 'second', '亞軍' => 'second',
    '第三名' => 'third', '季軍' => 'third', '小組第三名' => 'third',
    '第五名' => 'fifth',
    '第七名' => 'seventh',
    '合格' => 'advance', '進入第二回比賽' => 'advance', '進入第二回' => 'advance', '進入複賽' => 'advance', '16強' => 'advance',
];

// 快取
$parseDate = function ($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') {
        return null;
    }

    // Excel 日期序號（例如 39570）
    if (is_numeric($raw) && (float) $raw > 20000 && (float) $raw < 60000) {
        return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw))->format('Y-m-d');
    }

    // 其餘一律取數字：20050819 → 2005-08-19；只有年份 → 01-01
    $digits = preg_replace('/\D/', '', $raw);
    if (strlen($digits) >= 8) {
        return substr($digits, 0, 4).'-'.substr($digits, 4, 2).'-'.substr($digits, 6, 2);
    }
    if (strlen($digits) === 4) {
        return $digits.'-01-01';
    }

    return null;
};

$competitionCache = [];
$applicationCache = [];
$memberCache = [];
$memberIds = Member::whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))
    ->get(['id', 'name_zh', 'name_fn']);
foreach ($memberIds as $member) {
    if ($member->name_zh) {
        $memberCache[trim($member->name_zh)] = $member->id;
    }
}

$stats = [
    'rows' => 0, 'skipped_no_rank' => 0, 'skipped_unknown_result' => 0, 'skipped_no_date' => 0,
    'skipped_no_scheme' => 0, 'competitions' => 0, 'applications_new' => 0, 'applications_existing' => 0,
    'matched_member' => 0, 'no_member' => 0,
];
$byNature = [];
$samples = [];

echo ($commit ? "*** 正式寫入 ***\n" : "*** DRY RUN（加 --commit 才寫入）***\n");

DB::beginTransaction();

for ($r = 2; $r <= $sheet->getHighestRow(); $r++) {
    $name = $cell('J', $r);
    if ($name === '') {
        continue;
    }

    $rawResult = preg_replace('/\s+/u', '', $cell('H', $r));
    if (! isset($resultMap[$rawResult])) {
        if (in_array($rawResult, ['取消', '退出比賽', '不合格', '一勝二負', '', '/', '-'], true)) {
            $stats['skipped_no_rank']++;
        } else {
            $stats['skipped_unknown_result']++;
        }
        continue;
    }

    $title = $cell('D', $r);
    $rawDate = $cell('B', $r);
    $datePart = explode('~', $rawDate)[0];
    $endPart = explode('~', $rawDate)[1] ?? $datePart;

    $start = $parseDate($datePart);
    $end = $parseDate($endPart) ?: $start;

    // 日期欄空白時，退回「年份」欄（只要年份）
    if (! $start) {
        $year = preg_replace('/\D/', '', $cell('A', $r));
        $start = strlen($year) === 4 ? $year.'-01-01' : null;
        $end = $start;
    }

    if (! $start) {
        $stats['skipped_no_date']++;
        continue;
    }

    $nature = $cell('I', $r);
    $schemeTitle = $schemeByNature[$nature] ?? null;
    if (! $schemeTitle || ! isset($schemes[$schemeTitle])) {
        $stats['skipped_no_scheme']++;
        continue;
    }
    $scheme = $schemes[$schemeTitle];

    $cacheKey = $title.'|'.$start;
    if (! isset($competitionCache[$cacheKey])) {
        $competition = Competition::where('organization_id', $organizationId)
            ->where('title_zh', $title)
            ->whereDate('start_date', $start)
            ->first();

        if (! $competition) {
            $competition = new Competition([
                'organization_id' => $organizationId,
                'competition_score_id' => $scheme->id,
                'title_zh' => $title,
                'start_date' => $start,
                'end_date' => $end,
                'categories_weights' => '[]',
                'roles' => json_encode([
                    ['value' => 'athlete', 'label' => '運動員'],
                    ['value' => 'referee', 'label' => '裁判'],
                    ['value' => 'coach', 'label' => '教練'],
                    ['value' => 'staff', 'label' => '工作人員'],
                ]),
                'result_scores' => $scheme->toArray(),
                'published' => false,
            ]);
            $competition->save();
            $stats['competitions']++;
        }

        $competitionCache[$cacheKey] = $competition;
    }
    $competition = $competitionCache[$cacheKey];

    $roleText = preg_replace('/[（(].*?[）)]/u', '', $cell('E', $r));
    $role = $roleMap[$roleText] ?? 'athlete';

    $appKey = $competition->id.'|'.$name.'|'.$cell('F', $r).'|'.$cell('G', $r);
    if (! isset($applicationCache[$appKey])) {
        $application = CompetitionApplication::where('competition_id', $competition->id)
            ->where('name_zh', $name)
            ->where('category', $cell('F', $r) ?: null)
            ->where('weight', $cell('G', $r) ?: null)
            ->first();

        if ($application) {
            $stats['applications_existing']++;
        } else {
            $application = new CompetitionApplication([
                'competition_id' => $competition->id,
                'organization_id' => $organizationId,
            ]);
            $stats['applications_new']++;
        }
        $applicationCache[$appKey] = $application;
    }
    $application = $applicationCache[$appKey];

    $rank = $resultMap[$rawResult];
    $scores = $competition->score?->toArray() ?? ($competition->result_scores ?? []);

    $application->name_zh = $application->name_zh ?: $name;
    $application->display_name = $application->display_name ?: $name;
    $application->category = $application->category ?: ($cell('F', $r) ?: null);
    $application->weight = $application->weight ?: ($cell('G', $r) ?: null);
    $application->role = $application->role ?: $role;
    if (! $application->member_id) {
        $memberId = $memberCache[$name] ?? null;
        if ($memberId) {
            $application->member_id = $memberId;
            $stats['matched_member']++;
        } else {
            $stats['no_member']++;
        }
    }
    $application->result_rank = $rank;
    $application->result_score = (int) ($scores[$rank] ?? 0);
    $application->save();

    $stats['rows']++;
    $byNature[$nature] = ($byNature[$nature] ?? 0) + 1;

    if (count($samples) < 8) {
        $samples[] = [
            'competition' => $title, 'date' => $start, 'nature' => $nature, 'scheme' => $schemeTitle,
            'name' => $name, 'rank' => $rawResult.' → '.$rank, 'score' => $application->result_score,
            'member_id' => $application->member_id,
        ];
    }
}

if ($commit) {
    DB::commit();
} else {
    DB::rollBack();
}

echo json_encode($stats, JSON_UNESCAPED_UNICODE)."\n\n";
arsort($byNature);
echo "依性質匯入筆數: ".json_encode($byNature, JSON_UNESCAPED_UNICODE)."\n\n";
echo "範例:\n";
foreach ($samples as $sample) {
    echo '  '.json_encode($sample, JSON_UNESCAPED_UNICODE)."\n";
}
echo "\n目前 competitions=".Competition::count()." scored applications=".CompetitionApplication::whereNotNull('result_score')->count()."\n";
