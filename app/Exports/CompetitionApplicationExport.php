<?php

namespace App\Exports;

use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompetitionApplicationExport implements FromArray, WithHeadings, WithColumnWidths
{
    protected $competition, $columns;

    function __construct(Competition $competition)
    {
        $this->competition = $competition;
        // 欄位順序與 public/templates/athlete_list.xlsx 一致，
        // key 去掉表名後就是匯入時讀的第 2 列欄位代號（title 對應 organization）。
        $this->columns = [
            'organizations.title' => '屬會',
            'competition_applications.name_zh' => '中文名',
            'competition_applications.name_fn' => '外文名',
            'competition_applications.id_num' => '證件號碼',
            'competition_applications.gender' => '性別',
            'competition_applications.dob' => '出生日期',
            'competition_applications.belt_rank' => '帶別',
            'competition_applications.email' => '電郵',
            'competition_applications.mobile' => '手機號碼',
            'competition_applications.category' => '組別',
            'competition_applications.weight' => '重量',
            'competition_applications.role' => '角色',
            'competition_applications.referee_options' => '裁判',
            'competition_applications.staff_options' => '工作人員',
        ];
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    /**
     * 第 2 列是欄位代號，所以匯出的檔案可以直接修改後再匯入。
     */
    public function array(): array
    {
        return [
            $this->keys(),
            ...$this->collection()->map(fn ($row) => array_values($row))->all(),
        ];
    }

    private function keys(): array
    {
        return array_map(function ($column) {
            $key = Str::afterLast($column, '.');

            return $key === 'title' ? 'organization' : $key;
        }, array_keys($this->columns));
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, 'B' => 14, 'C' => 16, 'D' => 16, 'E' => 10, 'F' => 14, 'G' => 12,
            'H' => 26, 'I' => 16, 'J' => 16, 'K' => 12, 'L' => 12, 'M' => 24, 'N' => 24,
        ];
    }

    public function collection()
    {
        $roles = collect(json_decode($this->competition->roles));
        $categories = collect(json_decode($this->competition->categories_weights));
        $referee_options = collect(Config::item('referee_options'));
        $belt_ranks = collect(Config::item('belt_ranks'));
        $applications = CompetitionApplication::join('organizations', 'organizations.id', '=', 'competition_applications.organization_id')
            ->select(array_keys($this->columns))
            ->where('competition_id', $this->competition->id)
            ->get()->collect();
        $data = $applications->map(function ($application) use ($roles, $categories, $belt_ranks) {
            return [...$application->toArray(),
                'role' => $roles->filter(function ($role) use ($application) {
                    return $role->value == $application->role;
                })->first()->label ?? '',
                'category' => $categories->filter(function ($category) use ($application) {
                    return $category->code == $application->category;
                })->first()->name ?? '',
                'belt_rank' => $belt_ranks->filter(function ($belt_rank) use ($application) {
                    return $belt_rank->rankCode == $application->belt_rank;
                })->first()->name_zh ?? '',
                'gender' => $application->gender == 'M' ? '男' : '女',
                'referee_options' => $application->referee_options ? json_encode($application->referee_options) : '',
                'staff_options' => $application->staff_options ? json_encode($application->staff_options) : '',
            ];
        });

        return $data;
    }
    // public function drawings()
    // {

    //     $drawings = [];
    //     $data = $this->collection();
    //     foreach ($data as $key => $t) {
    //         $idx = $key + 2;
    //         $drawing = new Drawing();
    //         $drawing->setName('Logo');
    //         // $drawing->setPath(public_path($t->avatar_url));
    //         $drawing->setHeight(50);
    //         $drawing->setCoordinates("O$idx");
    //         $drawings[] = $drawing;
    //     }
    //     return $drawings;
    // }
}
