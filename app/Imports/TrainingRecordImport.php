<?php

namespace App\Imports;

use App\Models\AthleteSetting;
use App\Models\Organization;
use App\Models\TrainingRecord;
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
 * 匯入每月運動員訓練記錄。
 *
 * 對應的範本：public/templates/training_record_list.xlsx
 *  第 1 列 = 中文欄名（給人看）
 *  第 2 列 = 欄位代號（程式用，WithHeadingRow 讀這一列）
 *  第 3 列起 = 資料（一行 = 一位運動員一個月）
 *
 * 匯入會覆蓋該月該運動員原有的四項次數；四項都是 0 時會刪除該筆記錄。
 */
class TrainingRecordImport implements ToCollection, WithHeadingRow, WithStartRow, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public const HEADING_ROW = 2;
    public const START_ROW = 3;

    private Organization $organization;

    private int $imported = 0;

    private ?Collection $members = null;

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

            if ($this->isEmptyRow($cells)) {
                $rowNumber++;

                continue;
            }

            $errors = [];

            $month = $this->parseMonth($cells['month'] ?? null);
            $member = $this->findMember(
                $cells['member_number'] ?? null,
                $cells['member_name'] ?? null,
                $errors
            );

            if ($month === null) {
                $errors['month'][] = '月份格式不正確（請用 YYYY-MM，例如 2026-11）';
            }

            // 集訓記錄只接受集訓隊成員
            if ($member !== null && ! $this->isTeamMember($member->id)) {
                $errors['member_number'][] = '此會員不是集訓隊成員，請先到「運動員設定」加入';
            }

            $counts = [];

            foreach (TrainingRecord::POINT_FIELDS as $field) {
                $counts[$field] = $this->parseCount($cells[$field] ?? null, $field, $errors);
            }

            if ($errors !== [] || $month === null || $member === null) {
                foreach ($errors as $attribute => $messages) {
                    $this->onFailure(new Failure($rowNumber, $attribute, $messages));
                }

                $rowNumber++;

                continue;
            }

            $where = [
                'organization_id' => $this->organization->id,
                'member_id' => $member->id,
            ];

            $monthValue = $month->format('Y-m-d');

            if (array_sum($counts) === 0) {
                TrainingRecord::where($where)->whereDate('month', $monthValue)->delete();
            } else {
                TrainingRecord::updateOrCreate($where + ['month' => $monthValue], $counts);
            }

            $this->imported++;
            $rowNumber++;
        }
    }

    /**
     * 月份可填 2026-11、2026-11-01 或 Excel 日期。
     */
    private function parseMonth(mixed $raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            if (is_numeric($raw)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw))->startOfMonth();
            }

            $value = trim((string) $raw);

            // 2026-11 / 2026/11
            if (preg_match('/^(\d{4})[-\/](\d{1,2})$/', $value, $matches)) {
                $month = (int) $matches[2];

                if ($month < 1 || $month > 12) {
                    return null;
                }

                return Carbon::createFromDate((int) $matches[1], $month, 1)->startOfMonth();
            }

            // 2026-11-01 / 2026/11/01
            if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $value, $matches)) {
                $month = (int) $matches[2];

                if ($month < 1 || $month > 12) {
                    return null;
                }

                return Carbon::createFromDate(
                    (int) $matches[1],
                    $month,
                    (int) $matches[3]
                )->startOfMonth();
            }

            return Carbon::parse($value)->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseCount(mixed $raw, string $field, array &$errors): int
    {
        if ($raw === null || $raw === '') {
            return 0;
        }

        if (! is_numeric($raw) || (float) $raw < 0) {
            $errors[$field][] = '必須是 0 或以上的數字';

            return 0;
        }

        return (int) round((float) $raw);
    }

    /**
     * 用會員號碼或姓名找出本屬會會員。
     */
    private function findMember(mixed $number, mixed $name, array &$errors): mixed
    {
        $number = trim((string) $number);
        $name = trim((string) $name);
        $members = $this->members();

        if ($number !== '') {
            $digits = preg_replace('/\D/', '', $number);
            $padded = $digits === '' ? $number : str_pad($digits, 5, '0', STR_PAD_LEFT);
            $member = $members->first(fn ($item) => $item->member_number === $padded);

            if ($member) {
                return $member;
            }

            $errors['member_number'][] = '找不到會員號碼 '.$number.'（需為本屬會會員）';

            return null;
        }

        if ($name !== '') {
            $matches = $members->filter(function ($item) use ($name) {
                $names = array_filter([
                    trim((string) $item->name_zh),
                    trim((string) $item->name_fn),
                    trim((string) $item->display_name),
                ]);

                return in_array($name, $names, true);
            });

            if ($matches->count() === 1) {
                return $matches->first();
            }

            if ($matches->count() > 1) {
                $errors['member_name'][] = '有多位同名會員，請改用會員號碼';

                return null;
            }

            $errors['member_name'][] = '找不到會員 '.$name.'（需為本屬會會員）';

            return null;
        }

        $errors['member_number'][] = '請填會員號碼或會員姓名';

        return null;
    }

    private function members(): Collection
    {
        if ($this->members === null) {
            $this->members = Organization::find($this->organization->id)->members;
        }

        return $this->members;
    }

    private function isTeamMember(int $memberId): bool
    {
        return AthleteSetting::where('organization_id', $this->organization->id)
            ->where('member_id', $memberId)
            ->where('is_team_member', true)
            ->exists();
    }

    private function isEmptyRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
