<?php

namespace App\Imports;

use App\Models\Config;
use App\Models\Member;
use App\Models\MemberTier;
use App\Models\Organization;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * 導入屬會會員。
 *
 * 對應的範本：public/templates/member_list.xlsx
 *  第 1 列 = 中文欄名（給人看）
 *  第 2 列 = 欄位代號（程式用，WithHeadingRow 讀這一列）
 *  第 3 列起 = 資料
 */
class MemberImport implements ToCollection, WithHeadingRow, WithStartRow, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public const HEADING_ROW = 2;
    public const START_ROW = 3;

    private Organization $organization;

    private int $imported = 0;

    /** 本次檔案已用過的電郵，避免同一個檔案裡重覆匯入 */
    private array $seenEmails = [];

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    public function headingRow(): int
    {
        return self::HEADING_ROW;
    }

    public function startRow(): int
    {
        return self::START_ROW;
    }

    public function importedCount(): int
    {
        return $this->imported;
    }

    public function collection(Collection $rows): void
    {
        $rowNumber = self::START_ROW;

        foreach ($rows as $row) {
            $cells = $row->toArray();

            // 整列空白就跳過（仍然要讓列號前進，錯誤訊息才會對到 Excel 的列）
            if ($this->isEmptyRow($cells)) {
                $rowNumber++;

                continue;
            }

            $values = $this->normalize($cells);
            $errors = [];

            // 姓名：中文名與外文名至少要有一個
            if ($values['name_zh'] === '' && $values['name_fn'] === '') {
                $errors['name_zh'][] = '中文名與外文名至少要填寫一個';
            }

            if ($values['email_raw'] !== '' && $values['email'] === null) {
                $errors['email'][] = '電郵格式不正確';
            }

            if ($values['dob_raw'] !== '' && $values['dob'] === null) {
                $errors['dob'][] = '出生日期格式不正確（請用 YYYY-MM-DD）';
            }

            if ($values['gender_raw'] !== '' && $values['gender'] === null) {
                $errors['gender'][] = '性別請填「男」或「女」';
            }

            if ($values['tier_raw'] !== '' && $values['tier_code'] === null) {
                $errors['tier_code'][] = '會員級別不在設定清單內，請參考會員級別設定';
            }

            // 同一屬會已有相同電郵的會員就略過，避免重覆建立
            if ($values['email'] !== null) {
                $email = mb_strtolower($values['email']);

                if (isset($this->seenEmails[$email])) {
                    $errors['email'][] = '此電郵在本次檔案中重覆';
                } elseif (Member::where('email', $values['email'])
                    ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $this->organization->id))
                    ->exists()) {
                    $errors['email'][] = '此電郵的會員已存在於本屬會';
                }
            }

            if ($errors !== []) {
                foreach ($errors as $attribute => $messages) {
                    $this->onFailure(new Failure($rowNumber, $attribute, $messages));
                }

                $rowNumber++;

                continue;
            }

            if ($values['email'] !== null) {
                $this->seenEmails[mb_strtolower($values['email'])] = true;
            }

            $member = Member::create([
                'name_zh' => $values['name_zh'] ?: null,
                'name_fn' => $values['name_fn'] ?: null,
                'display_name' => $values['display_name'] ?: trim($values['name_zh'] . ' ' . $values['name_fn']),
                'gender' => $values['gender'],
                'dob' => $values['dob'],
                'email' => $values['email'],
                'mobile' => $values['mobile'] ?: null,
                'nationality' => $values['nationality'] ?: null,
                'country' => $values['country'] ?: null,
                'address' => $values['address'] ?: null,
            ]);

            $member->organizations()->attach($this->organization->id);

            if ($values['tier_code'] !== null) {
                MemberTier::create([
                    'member_id' => $member->id,
                    'tier_code' => $values['tier_code'],
                    'valid_at' => Carbon::today()->toDateString(),
                    'expired_at' => null,
                ]);
            }

            $this->imported++;
            $rowNumber++;
        }
    }

    /**
     * 把一列資料整理成可直接寫入資料庫的格式。
     *
     * @param  array<string, mixed>  $cells
     * @return array<string, mixed>
     */
    private function normalize(array $cells): array
    {
        $get = fn (string $key) => trim((string) ($cells[$key] ?? ''));

        $emailRaw = $get('email');
        $dobRaw = $get('dob');
        $genderRaw = $get('gender');
        $tierRaw = $get('tier_code');

        return [
            'name_zh' => $get('name_zh'),
            'name_fn' => $get('name_fn'),
            'display_name' => $get('display_name'),
            'email_raw' => $emailRaw,
            'email' => $emailRaw !== '' && filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ? $emailRaw : null,
            'dob_raw' => $dobRaw,
            'dob' => $this->normalizeDate($dobRaw),
            'gender_raw' => $genderRaw,
            'gender' => $this->normalizeGender($genderRaw),
            'mobile' => $get('mobile'),
            'nationality' => $get('nationality'),
            'country' => $get('country'),
            'address' => $get('address'),
            'tier_raw' => $tierRaw,
            'tier_code' => $this->normalizeTier($tierRaw),
        ];
    }

    private function isEmptyRow(array $cells): bool
    {
        foreach ($cells as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * 性別統一存成 'M'(男) / 'F'(女)，
     * 與 Member/Profile.vue、Competition/ApplicationForm.vue 的取值一致。
     * （後台 Admin/Members.vue 用的 0/1 也一併接受，方便沿用舊習慣填寫）
     */
    private function normalizeGender(string $value): ?string
    {
        return match (mb_strtolower($value)) {
            '男', 'm', 'male', '0' => 'M',
            '女', 'f', 'female', '1' => 'F',
            default => null,
        };
    }

    /**
     * 會員級別可填設定裡的代號（TEAM_A）或名稱（代表隊A）。
     */
    private function normalizeTier(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $tiers = collect(Config::item('member_tiers') ?: []);
        $tier = $tiers->first(function ($tier) use ($value) {
            return mb_strtolower((string) $tier->value) === mb_strtolower($value)
                || mb_strtolower((string) $tier->label) === mb_strtolower($value);
        });

        return $tier->value ?? null;
    }

    /**
     * 出生日期接受 Excel 日期序號或各種常見字串格式。
     */
    private function normalizeDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
