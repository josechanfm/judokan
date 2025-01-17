<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use GuzzleHttp\Client;
use App\Models\Organization;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Form;
use Inertia\Inertia;
use App\Exports\EntryExport;
use App\Models\Entry;
use App\Models\EntryRecord;
use App\Models\FormField;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use PDF;


class EntryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Form $form)
    {
        //$form=Form::with('fields')->find($form->id);
        $entries = $form->tableEntries();
        return Inertia::render('Organization/FormEntries', [
            'organization' => session('organization'),
            'form' => $form,
            'entries' => $entries,
            'fields' => $form->fields,
            'entryColumns' => $form->entry_columns()
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
    public function update(Request $request, Form $form, Entry $entry)
    {
        //
        collect($request->fields)->map(function ($entryField, $key) use ($entry) {
            if (is_array($entryField)) {
                if (isset($entryField['file'])) {
                    $file = $entryField['blob'];
                    $path = Storage::putFile('public/images/forms/photos', $file);
                    $entryField = Storage::url($path);
                }
            }
            $entry->records()->updateOrCreate(
                [
                    'form_field_id' => $key,
                ],
                [
                    'entry_id' => $entry->id,
                    //                'name_en' => $category['name_en'],
                    'form_field_id' => $key,
                    'field_value' => $entryField,
                ]
            );
        });
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Form $form, $id)
    {
        EntryRecord::where('entry_id', $id)->delete();
        Entry::where('id', $id)->delete();

        return Redirect()->back();
        //
    }

    public function export(Form $form)
    {
        // dd($form->excelRecords());
        return Excel::download(new EntryExport($form), 'member.xlsx');
    }

    public function success(Form $form, Entry $entry)
    {
        Session::flash('entry', $entry->id);
        // dd($form, $entry);
        return redirect()->route('form.entry.success', ['form' => $form->id, 'entry' => $entry->id]);
    }
    public function entrySuccess(Form $form, Entry $entry, Request $request)
    {
        $entry_records = EntryRecord::where('entry_id', $entry->id)->with('form_field')->get();
        $form_fields = FormField::where('form_id', $form->id)->get();
        $table_data = [];
        if (strtoupper($request->format) == 'PDF') {
            if (!session('entryPdf') || session('entryPdf') != $entry->id) {
                return redirect()->route('/');
            }
            collect($form_fields)->map(function ($field, $key) use ($entry_records, &$table_data) {
                $entry_record = collect($entry_records)->filter(function ($item) use ($field) {
                    return $item->form_field_id == $field->id;
                })->first();
                if ($field->type == 'radio') {
                    $value = array_filter(json_decode($field->options), function ($item) use ($entry_record) {
                        return $item->value === $entry_record?->field_value;
                    });
                    // dd($value);
                    $table_data[$field->field_label] = reset($value)->label ?? '';
                    // 
                } else if ($field->type == 'checkbox') {
                    $value = array_filter(json_decode($field->options), function ($item) use ($entry_record) {
                        return in_array($item->value, json_decode($entry_record->field_value));
                    });
                    $labels = [];
                    foreach ($value as $item) {
                        $labels[] = $item->label;
                    }
                    $result = implode(',', $labels);
                    $table_data[$field->field_label] = $result;
                } else {
                    $table_data[$field->field_label] = $entry_record?->field_value;
                };
            });
            // dd($table_data);
            $pdf = PDF::loadView('Entry/EntrySuccess', [
                'table_data' => $table_data,
            ]);
            // return view('Entry/EntrySuccess', [
            //     'table_data' => $table_data,
            // ]);
            $pdf->render();
            return $pdf->stream('receipt.pdf', array('Attachment' => false));
        } else {
            if (!session('entry') || session('entry') != $entry->id) {
                return redirect()->route('/');
            }
            Session::flash('entryPdf', $entry->id);
            return Inertia::render('Form/Success', [
                'form' => $form,
                'entry' => $entry,
                'entry_records' => $entry_records,
            ]);
        }
    }

}
