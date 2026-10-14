<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\Organization;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MemberExport implements FromArray, WithHeadings, WithColumnWidths
{
    private ?Organization $organization;

    public function __construct(?Organization $organization = null)
    {
        $this->organization = $organization;
    }

    /**
     * 第 2 列（欄位代號）＋資料列，第 1 列由 headings() 產生，
     * 整個格式和匯入用的 public/templates/member_list.xlsx 一致，
     * 所以「匯出 → 修改 → 匯入」可以直接互通。
     */
    public function array(): array
    {
        $query = Member::query()->with('organizations');

        if ($this->organization) {
            $query->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organization->id));
        }

        $rows = $query->orderBy('id')->get()->map(fn (Member $member) => [
            $member->name_zh,
            $member->name_fn,
            $member->display_name,
            $member->gender === 'M' ? '男' : ($member->gender === 'F' ? '女' : ''),
            $member->dob,
            $member->email,
            $member->mobile,
            $member->nationality,
            $member->country,
            $member->address,
            $member->current_tier->tier_code ?? '',
        ])->all();

        return [
            ['name_zh', 'name_fn', 'display_name', 'gender', 'dob', 'email', 'mobile', 'nationality', 'country', 'address', 'tier_code'],
            ...$rows,
        ];
    }

    public function headings(): array
    {
        return ['中文名', '外文名', '顯示名稱', '性別（男 / 女）', '出生日期（YYYY-MM-DD）', '電郵', '手機號碼', '國籍', '國家 / 地區', '地址', '會員級別'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, 'B' => 18, 'C' => 20, 'D' => 14, 'E' => 22, 'F' => 26,
            'G' => 16, 'H' => 12, 'I' => 14, 'J' => 32, 'K' => 20,
        ];
    }
}
