<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Models\Competition;
use App\Models\CompetitionApplication;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithDrawings;

class CompetitionApplicationExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $competition, $columns;

    function __construct(Competition $competition)
    {

        $this->competition = $competition;
        $this->columns = [
            'organizations.title' => '屬會',
            'competition_applications.name_zh' => '姓名(中文)',
            'competition_applications.name_fn' => '姓名(外文)',
            'competition_applications.id_num' => '證件號碼',
            'competition_applications.dob' => '出生日期',
            'competition_applications.gender' => '性別',
            'competition_applications.belt_rank' => '帶別',
            'competition_applications.email' => '電郵',
            'competition_applications.mobile' => '手機號碼',
            'competition_applications.role' => '角色',
            'competition_applications.category' => '組別',
            'competition_applications.weight' => '重量',
            'competition_applications.referee_options' => '裁判',
            'competition_applications.staff_options' => '工作人員',
        ];
    }
    public function headings(): array
    {
        $columnHeaders = array_values($this->columns);
        // array_push($columnHeaders, 'base64_decode');
        return $columnHeaders;
    }
    public function collection()
    {
        // dd('aaa');
        $roles = collect($this->competition->roles);
        $categories = collect($this->competition->categories_weights);

        $applications = CompetitionApplication::join('organizations', 'organizations.id', '=', 'competition_applications.organization_id')
            ->select(array_keys($this->columns))
            ->where('competition_id', $this->competition->id)
            ->get()->collect();

        $data = $applications->map(function ($application) use ($roles, $categories) {
            return [...$application->toArray(), 'role' => $roles->filter(function ($role) use ($application) {
                return $role['value'] == $application->role;
            })->first()['label'], 'category' => $categories->filter(function ($category) use ($application) {
                return $category['code'] == $application->category;
            })->first()['name']];
        });

        // dd($applications);
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
