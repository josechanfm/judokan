<?php

namespace App\Imports;

use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Config;
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

    /**
     * 逐列匯入比賽報名資料。
     *
     * 每列都會先驗證（屬會、姓名、證件號碼、性別、出生日期、帶別、組別、重量、角色），
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

            // 屬會：用全名比對
            $organizationName = trim((string) ($row['organization'] ?? ''));
            $organization = $organizationName !== ''
                ? Organization::where('full_name', $organizationName)->orWhere('title', $organizationName)->first()
                : null;
            if (! $organization) {
                $fail('organization', $organizationName === '' ? '屬會不可為空' : "找不到屬會「{$organizationName}」");
                continue;
            }
            $data['organization_id'] = $organization->id;

            if ($data['name_zh'] === '' && $data['name_fn'] === '') {
                $fail('name_zh', '中文名與外文名至少要填一個');
                continue;
            }

            if ($data['id_num'] === '') {
                $fail('id_num', '證件號碼不可為空');
                continue;
            }

            // 性別
            $gender = $this->normalizeGender($row['gender'] ?? null);
            if (! $gender) {
                $fail('gender', '性別必須是「男」或「女」');
                continue;
            }
            $data['gender'] = $gender;

            // 出生日期（支援 Excel 日期序號與字串）
            $dob = $this->normalizeDate($row['dob'] ?? null);
            if (! $dob) {
                $fail('dob', '出生日期格式不正確（請用 YYYY-MM-DD）');
                continue;
            }
            $data['dob'] = $dob;

            // 帶別：可填中文名（白帶）或代號（white）
            $beltRankName = trim((string) ($row['belt_rank'] ?? ''));
            $beltRank = $beltRankName === '' ? null : $beltRanks->first(
                fn ($item) => $item->name_zh === $beltRankName || $item->rankCode === $beltRankName
            );
            if ($beltRankName !== '' && ! $beltRank) {
                $fail('belt_rank', "找不到帶別「{$beltRankName}」");
                continue;
            }
            $data['belt_rank'] = $beltRank->rankCode ?? null;

            // 組別：這場比賽有開放的組別（可填組別名稱或代號）
            $categoryName = trim((string) ($row['category'] ?? ''));
            $category = $categories->first(
                fn ($item) => $item->code === $categoryName || $item->name === $categoryName
            );
            if (! $category) {
                $fail('category', $categoryName === '' ? '組別不可為空' : "這場比賽沒有「{$categoryName}」這個組別");
                continue;
            }
            $data['category'] = $category->code;

            // 重量：依性別與組別找對應的量級（可填重量名稱或代號）
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

            // 角色：這場比賽有開放的角色（可填角色名稱或代號）
            $roleName = trim((string) ($row['role'] ?? ''));
            $role = $roles->first(
                fn ($item) => ($item->value ?? null) === $roleName || ($item->label ?? null) === $roleName
            );
            if (! $role) {
                $fail('role', $roleName === '' ? '角色不可為空' : "這場比賽沒有「{$roleName}」這個角色");
                continue;
            }
            $data['role'] = $role->value;

            // 同一份檔案中不可重複
            $uniqueKey = implode('|', [$data['id_num'], $data['category'], $data['weight']]);
            if (isset($seen[$uniqueKey])) {
                $fail('id_num', '同一份檔案中重複報名（相同證件號碼、組別與重量）');
                continue;
            }
            $seen[$uniqueKey] = true;

            // 同一場比賽中不可重複報名
            $exists = CompetitionApplication::where('competition_id', $this->competition->id)
                ->where('id_num', $data['id_num'])
                ->where('category', $data['category'])
                ->where('weight', $data['weight'])
                ->exists();
            if ($exists) {
                $fail('id_num', '這位選手已經報名相同的組別與重量');
                continue;
            }

            CompetitionApplication::create($data);
            $this->importedCount++;
        }
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
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
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
    