<?php

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Config;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;
// use Maatwebsite\Excel\Concerns\WithDrawings;

class CompetitionApplicationImport implements ToCollection, WithStartRow, SkipsOnFailure, WithHeadingRow
{
    /**
     * @return \Illuminate\Support\Collection
     */
    use SkipsFailures, Importable;

    /**
     * @var Contest $contest
     */
    private Competition $competition;

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

    /**
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $data = [...$row];
            $data['organization_id'] = Organization::where('full_name',$row['organization'])->first()->id;
            $data['belt_rank'] = collect(Config::item("belt_ranks"))->filter(function ($item) use ($row){
                return $item->name_zh === $row['belt_rank'];
            })->first()->rankCode;
            $data['competition_id'] = $this->competition->id;
            $data['gender'] = $data['gender'] == '男'? 'M' : 'F';
            $data['category'] = collect(Config::item("categories_weights"))->filter(function ($item) use ($row){
                return $item === $row['category'];
            })->first();
            $data['role'] = collect(Config::item("competition_roles"));
            unset($data['organization']);
            dd($data);
            $validator = Validator::make($row->toArray(), [
                'competition_id' => 'required|in:M,F',
                'organization_id' => 'required',
                'name_zh' => 'nullable|string',
                'name_fn' => 'required',
                'id_num' => 'nullable',
                'gender' => 'nullable',
                'dob' => 'nullable',
                'belt_rank'=> 'nullable',
                'email'=>'nullable',
                'mobile'=>'nullable',
                'category'=>'required',
                'weight'=>'required',
                'role'=>'required',
            ]);
            // 創建代表隊資料
            $this->createCompetitionApplications($data);
        }
    }
    private function createCompetitionApplications($row): CompetitionApplication
    {
        // 創建代表隊資料
        return CompetitionApplication::firstOrCreate($row);
    }

}
    