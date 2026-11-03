<?php

namespace App\Imports;

use App\Models\Organization;
use App\Models\TrainingCamp;
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
 * 匯入集訓出席名單。
 *
 * 對應的範本：public/templates/training_camp_list.xlsx
 *  第 1 列 = 中文欄名（給人看）
 *  第 2 列 = 欄位代號（程式用，WithHeadingRow 讀這一列）
 *  第 3 列起 = 資料（一行 = 一位會員的出席記錄）
 *
 * 同一日、同一名稱的列會歸入同一場集訓（以第一列的分數為準）。
 */
class TrainingCampImport implements ToCollection, WithHeadingRow, WithStartRow, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public const HEADING_ROW = 2;
    public const START_ROW = 3;

    private Organization $organization;

    private int $imported = 0;

    /** @var array<string, TrainingCamp> 本次檔案已建立的集訓 */
    private array $camps = [];

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

            $date = $this->parseDate($cells['camp_date'] ?? null);
            $title = trim((string) ($cells['camp_title'] ?? ''));
            $points = $this->parsePoints($cells['points'] ?? null, $errors);
            $member = $this->findMember(
                $cells['member_number'] ?? null,
                $cells['member_name'] ?? null,
                $errors
            );

            if ($date === null) {
                $errors['camp_date'][] = '集訓日期格式不正確（請用 YYYY-MM-DD）';
            }

            if ($title === '') {
                $errors['camp_title'][] = '集訓名稱不能空白';
            }

            if ($errors !== [] || $date === null || $member === null) {
                foreach ($errors as $attribute => $messages) {
                    $this->onFailure(new Failure($rowNumber, $attribute, $messages));
                }

                $rowNumber++;

                continue;
            }

            $camp = $this->camp($date, $title, $points);

            if ($camp->members()->where('members.id', $member->id)->exists()) {
                $this->onFailure(new Failure(
                    $rowNumber,
                    'member_number',
                    ['此會員已在此場集訓的出席名單內']
                ));
            } else {
                $camp->members()->attach($member->id);
                $this->imported++;
            }

            $rowNumber++;
        }
    }

    /**
     * 集訓日期可填 Excel 日期或 YYYY-MM-DD 文字。
     */
    private function parseDate(mixed $raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            if (is_numeric($raw)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw))->startOfDay();
            }

            return Carbon::parse((string) $raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 每次集訓的分數，空白視為 0 分。
     */
    private function parsePoints(mixed $raw, array &$errors): int
    {
        if ($raw === null || $raw === '') {
            return 0;
        }

        if (! is_numeric($raw) || (float) $raw < 0) {
            $errors['points'][] = '分數必須是 0 或以上的數字';

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

    private function camp(Carbon $date, string $title, int $points): TrainingCamp
    {
        $key = $date->format('Y-m-d').'|'.$title;

        if (isset($this->camps[$key])) {
            return $this->camps[$key];
        }

        return $this->camps[$key] = TrainingCamp::firstOrCreate(
            [
                'organization_id' => $this->organization->id,
                'camp_date' => $date->format('Y-m-d'),
                'title' => $title,
            ],
            ['points' => $points]
        );
    }

    private function members(): Collection
    {
        if ($this->members === null) {
            $this->members = Organization::find($this->organization->id)->members;
        }

        return $this->members;
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
