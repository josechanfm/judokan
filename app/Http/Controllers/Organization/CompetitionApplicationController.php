<?php

namespace App\Http\Controllers\Organization;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Config;
use App\Models\Competition;
use App\Models\CompetitionApplication;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use PDF;
use App\Exports\CompetitionApplicationExport;
use App\Imports\CompetitionApplicationImport;
use App\Mail\TestMail;
use App\Models\Organization;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class CompetitionApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Competition $competition)
    {
        $competition->applications;
        Session::put('competitionId', $competition->id);
        return Inertia::render('Organization/CompetitionApplications', [
            'competitionResults' => Config::item('competition_results'),
            'organizations' => Organization::all(),
            'competition' => $competition
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Competition $competition, $id)
    {
        $competitionApplication = CompetitionApplication::find($id);
        // return response()->json($competitionApplication);
        // return response()->json($request->all());
        $data = $request->all();
        // dd($competition->result_scores, $data);
        if (!empty($data['result_rank']) && !empty($competition->result_scores)) {
            $score = $competition->score->toArray();
            $data['result_score'] = $competition->result_scores[$data['result_rank']];
        }
        $competitionApplication->update($data);
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Competition $competition, $id)
    {
        $competitionApplication = CompetitionApplication::find($id);
        if ($competitionApplication->avatar) {
            Storage::delete($competitionApplication->avatar);
        }

        $competitionApplication->delete();

        return redirect()->back();
    }

    public function receipts(Competition $competition, Request $request)
    {

        if (!session('competitionId') || session('competitionId') != $competition->id || $request->applicationIds==null) {
            return redirect()->route('/');
        }
        
        $applications = CompetitionApplication::whereIn('id', explode(',', $request->applicationIds))->get();
        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        // return view('Competition.ApplicationReceipt',[
        //     'competition'=>$competition,
        //     'applications'=>$applications
        // ]);
        $pdf = PDF::loadView('Competition.ApplicationReceipt', [
            'competition'=>$competition,
            'applications'=>$applications
        ]);
        $pdf->render();
        return $pdf->stream('receipts.pdf', array('Attachment' => false));
    }
    public function export(Competition $competition)
    {

        //echo $applications;
        //dd('aa');
        //dd($competition);
        // $baser64String = 'iVBORw0KGgoAAAANSUhEUgAAAJ4AAACeCAYAAADDhbN7AAAAAXNSR0IArs4c6QAAAsxJREFUeF7t0kEJACAABEGNaVizKRjC/cwVOFhmnr3OMAU+F5jgfS7u7hUAD4SkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOL7pc5wZFewbOAAAAAElFTkSuQmCC';
        // $image = base64_decode($baser64String);
        // dd($image);
        // $path = Storage::disk('public')->put('images/competitions/test.png', $image);
        // $data['avatar'] = $path;
        // dd($path);
        $data = new CompetitionApplicationExport($competition);
        return Excel::download($data, 'applications.xlsx');
    }
    public function import(Request $request,Competition $competition) {
        // dd($competition);
        $import = new CompetitionApplicationImport($competition);

        $import->import(request()->file('file'));
    }
    public function success(CompetitionApplication $competitionApplication)
    {
        Session::flash('competitionApplication', $competitionApplication->id);
        return Inertia::render('Competition/Success', [
            'organizations' => Organization::all(),
            'belt_ranks' => Config::item("belt_ranks"),
            'application' => CompetitionApplication::with('competition')->find($competitionApplication->id)
        ]);
    }

    public function sendApplicationEmail(CompetitionApplication $competitionApplication)
    {
        $application = CompetitionApplication::with('competition')->find($competitionApplication->id);
        $pdf = PDF::loadView('Competition.ApplicationSuccess', [
            'organizations' => Organization::all()->toArray(),
            'belt_ranks' => Config::item("belt_ranks"),
            'application' => $application,
        ]);
        $path = 'public/pdf/applications/' . $application->competition->title_zh . $application->name_fn . '.pdf';
        Storage::put($path, $pdf->output());
        $mailData = [
            'title' => '恭喜你已成功報名',
            'subject' => '比賽報名表',
            'view_name' => 'mail.applicationMail',
            'file_path' => $path,
        ];

        Mail::to(CompetitionApplication::with('competition')->find($competitionApplication->id)->email)->send(new TestMail($mailData));

        return redirect()->back();
    }
}
