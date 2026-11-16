<?php

namespace App\Imports;

use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Config;
use App\Models\Member;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CompetitionApplicationImport implements ToCollection, WithStartRow, SkipsOnFailure, WithHeadingRow
{
    /**
     * @return \Illuminate\Support\Collection
     */
    use SkipsFailures, Importable;

    /**
     * @var Competition $competition
     */
    private Competition $competition;

    /** @var int 成功匯入的筆數 */
    private int $importedCount = 0;

    /** @var int 其中成功綁定會員的筆數 */
    private int $boundCount = 0;

    public function __construct(Competition $competition)
    {
        $this->competition = $competition;
    }

    public function startRow(): int
    {
        return 3;
    }

    public function headingRow(): int
    {
        return 2;
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    public function boundCount(): int
    {
        return $this->boundCount;
    }

    /**
     * 逐列匯入比賽報名資料。
     *
     * 必填欄位只有：中文名、出生日期、性別、組別、重量。
     * 其他欄位（屬會、外文名、證件號碼、帶別、電郵、手機、角色）都可以留空；
     * 屬會預設為目前操作的屬會，角色預設為運動員。
     * 若中文名＋性別對到會員，會自動綁定 member_id，之後匯入的成績就會計入這位會員。
     * 通過者寫入資料庫，不通過者記到 failures()，回傳時會告知前端是 Excel 的第幾列。
     *
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows): void
    {
        $roles = collect($this->decodeOption($this->competition->roles));
        $categories = collect($this->decodeOption($this->competition->categories_weights));
        $beltRanks = collect(Config::item('belt_ranks'));

        // 屬會欄位可留空，預設使用目前登入的屬會
        $defaultOrganization = session('organization')
            ?: Organization::find($this->competition->organization_id);

        $seen = [];
        $rowNumber = $this->startRow() - 1;

        foreach ($rows as $row) {
            $rowNumber++;

            // 整列空白：略過但仍要推進列號，才能對應到 Excel 的第幾列
            if (collect($row)->filter(fn ($value) => $value !== null && trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            $fail = function (string $attribute, string $message) use ($rowNumber, $row) {
                $this->onFailure(new Failure($rowNumber, $attribute, [$message], $row->toArray()));
            };

            $data = [
                'competition_id' => $this->competition->id,
                'name_zh' => trim((string) ($row['name_zh'] ?? '')),
                'name_fn' => trim((string) ($row['name_fn'] ?? '')),
                'id_num' => trim((string) ($row['id_num'] ?? '')),
                'email' => trim((string) ($row['email'] ?? '')),
                'mobile' => trim((string) ($row['mobile'] ?? '')),
            ];

            // 中文名必填
            if ($data['name_zh'] === '') {
                $fail('name_zh', '中文名不可為空');
                continue;
            }

            // 屬會：可留空（預設目前操作的屬會），有填就必須找得到
            $organizationName = trim((string) ($row['organization'] ?? ''));
            $organization = $defaultOrganization;
            if ($organizationName !== '') {
                $organization = Organization::where('full_name', $organizationName)
                    ->orWhere('title', $organizationName)
                    ->first();
                if (! $organization) {
                    $fail('organization', "找不到屬會「{$organizationName}」");
                    continue;
                }
            }
            if (! $organization) {
                $fail('organization', '找不到屬會，請重新登入或填寫屬會');
                continue;
            }
            $data['organization_id'] = $organization->id;

            // 性別必填
            $gender = $this->normalizeGender($row['gender'] ?? null);
            if (! $gender) {
                $fail('gender', '性別必須是「男」或「女」');
                continue;
            }
            $data['gender'] = $gender;

            // 出生日期必填（支援 Excel 日期序號與字串）
            $dobValue = $row['dob'] ?? null;
            $dob = $this->normalizeDate($dobValue);
            if (! $dob) {
                $fail(
                    'dob',
                    $dobValue === null || trim((string) $dobValue) === ''
                        ? '出生日期不可為空'
                        : '出生日期格式不正確（請用 YYYY-MM-DD）'
                );
                continue;
            }
            $data['dob'] = $dob;

            // 帶別：選填，可填中文名（白帶）或代號（white）
            $beltRankName = trim((string) ($row['belt_rank'] ?? ''));
            $beltRank = $beltRankName === '' ? null : $beltRanks->first(
                fn ($item) => $item->name_zh === $beltRankName || $item->rankCode === $beltRankName
            );
            if ($beltRankName !== '' && ! $beltRank) {
                $fail('belt_rank', "找不到帶別「{$beltRankName}」");
                continue;
            }
            $data['belt_rank'] = $beltRank->rankCode ?? null;

            // 組別：必填，這場比賽有開放的組別（可填組別名稱或代號）
            $categoryName = trim((string) ($row['category'] ?? ''));
            $category = $categories->first(
                fn ($item) => $item->code === $categoryName || $item->name === $categoryName
            );
            if (! $category) {
                $fail('category', $categoryName === '' ? '組別不可為空' : "這場比賽沒有「{$categoryName}」這個組別");
                continue;
            }
            $data['category'] = $category->code;

            // 重量：必填，依性別與組別找對應的量級（可填重量名稱或代號）
            $weightName = trim((string) ($row['weight'] ?? ''));
            $weightList = collect($category->{$gender === 'M' ? 'male' : 'female'} ?? []);
            $weight = $weightList->first(
                fn ($item) => $item->code === $weightName || $item->name === $weightName
            );
            if (! $weight) {
                $fail('weight', $weightName === '' ? '重量不可為空' : "「{$category->name}」沒有「{$weightName}」這個量級");
                continue;
            }
            $data['weight'] = $weight->code;

            // 角色：選填，預設運動員（可填角色名稱或代號）
            $roleName = trim((string) ($row['role'] ?? ''));
            if ($roleName === '') {
                $data['role'] = 'athlete';
            } else {
                $role = $roles->first(
                    fn ($item) => ($item->value ?? null) === $roleName || ($item->label ?? null) === $roleName
                );
                if (! $role) {
                    $fail('role', "這場比賽沒有「{$roleName}」這個角色");
                    continue;
                }
                $data['role'] = $role->value;
            }

            // 用中文名＋性別比對會員，比對到就綁定，成績才會計入這位會員
            $member = $this->findMember($data['name_zh'], $data['gender']);
            if ($member) {
                $data['member_id'] = $member->id;
                $data['name_fn'] = $data['name_fn'] ?: (string) $member->name_fn;
                $data['id_num'] = $data['id_num'] ?: (string) $member->id_card_number;
                $data['email'] = $data['email'] ?: (string) $member->email;
                $data['mobile'] = $data['mobile'] ?: (string) $member->mobile;
                $data['display_name'] = $member->display_name ?: ($data['name_zh'] ?: $member->name_fn);
            } else {
                $data['display_name'] = $data['name_zh'] ?: $data['name_fn'];
            }

            // 同一份檔案中不可重複
            $uniqueKey = implode('|', [
                $data['member_id'] ?? 'guest-'.($data['id_num'] ?: $data['name_zh']),
                $data['category'],
                $data['weight'],
            ]);
            if (isset($seen[$uniqueKey])) {
                $fail('name_zh', '同一份檔案中重複報名（相同會員、組別與重量）');
                continue;
            }
            $seen[$uniqueKey] = true;

            // 同一場比賽中不可重複報名
            $existing = $this->findApplication($data);

            if ($existing) {
                // 既有資料還沒綁定會員時，把這筆報名（及其成績）綁到會員
                if (empty($data['member_id']) || $existing->member_id) {
                    $fail('name_zh', '這位選手已經報名相同的組別與重量');
                    continue;
                }

                $existing->member_id = $data['member_id'];
                $existing->name_fn = $existing->name_fn ?: $data['name_fn'];
                $existing->display_name = $existing->display_name ?: $data['display_name'];
                $existing->id_num = $existing->id_num ?: ($data['id_num'] ?: null);
                $existing->email = $existing->email ?: $data['email'];
                $existing->mobile = $existing->mobile ?: $data['mobile'];
                $existing->dob = $existing->dob ?: $data['dob'];
                $existing->gender = $existing->gender ?: $data['gender'];
                $existing->belt_rank = $existing->belt_rank ?: $data['belt_rank'];
                $existing->organization_id = $existing->organization_id ?: $data['organization_id'];
                $existing->save();

                $this->boundCount++;
                $this->importedCount++;
                continue;
            }

            CompetitionApplication::create($data);
            if (! empty($data['member_id'])) {
                $this->boundCount++;
            }
            $this->importedCount++;
        }
    }

    /**
     * 找出同一場比賽、同一組別量級的既有報名。
     */
    private function findApplication(array $data): ?CompetitionApplication
    {
        $base = CompetitionApplication::where('competition_id', $this->competition->id)
            ->where('category', $data['category'])
            ->where('weight', $data['weight']);

        if (! empty($data['member_id'])) {
            $existing = (clone $base)->where('member_id', $data['member_id'])->first();
            if ($existing) {
                return $existing;
            }
        } elseif ($data['id_num'] !== '') {
            $existing = (clone $base)->where('id_num', $data['id_num'])->first();
            if ($existing) {
                return $existing;
            }
        }

        return $base->where('name_zh', $data['name_zh'])
            ->where(fn ($query) => $query->where('gender', $data['gender'])->orWhereNull('gender'))
            ->first();
    }

    /**
     * 用中文名＋性別比對會員。
     *
     * 先找目前屬會的會員；找不到時，若全系統只有一位同名同性別的會員，也視為會員。
     */
    private function findMember(string $nameZh, string $gender): ?Member
    {
        // 會員資料的性別舊資料可能是 0/1，兩種寫法都接受
        $genderValues = $gender === 'M' ? ['M', 'm', '0', 0] : ['F', 'f', '1', 1];
        $organization = session('organization');

        if ($organization) {
            $member = Member::where('name_zh', $nameZh)
                ->whereIn('gender', $genderValues)
                ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $organization->id))
                ->first();

            if ($member) {
                return $member;
            }
        }

        $matches = Member::where('name_zh', $nameZh)
            ->whereIn('gender', $genderValues)
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * 比賽的 roles / categories_weights 在資料庫中是雙重編碼的 JSON 字串，
     * 這裡一律正規化成陣列（元素是 stdClass），避免直接存取時噴錯。
     */
    private function decodeOption($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded);
            }
        } else {
            $decoded = $value;
        }

        if (! is_array($decoded)) {
            return [];
        }

        // 統一成物件，讓下面的 $item->code / $item->name 兩種來源都能用
        return array_map(
            fn ($item) => is_array($item) ? json_decode(json_encode($item)) : $item,
            $decoded
        );
    }

    /**
     * 接受 男/m/male/M 與 女/f/female/F。
     */
    private function normalizeGender($value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        return match ($value) {
            '男', 'm', 'male' => 'M',
            '女', 'f', 'female' => 'F',
            default => null,
        };
    }

    /**
     * 接受 Excel 日期序號或 Y-m-d / Y/m/d / d-m-Y 等字串。
     */
    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            }

            return Carbon::parse(trim((string) $value))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
    