<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Imports\TrainingCampImport;
use App\Models\Organization;
use App\Models\TrainingCamp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class TrainingCampController extends Controller
{
    /**
     * 集訓記錄列表（會長用來記錄會員有沒有出席集訓）。
     */
    public function index(Request $request)
    {
        $organizationId = session('organization')->id;
        $filters = $this->resolveFilters($request);

        $camps = TrainingCamp::query()
            ->where('organization_id', $organizationId)
            ->whereDate('camp_date', '>=', $filters['start'])
            ->whereDate('camp_date', '<=', $filters['end'])
            ->when($filters['keyword'] !== '', function ($query) use ($filters) {
                $query->where('title', 'like', '%'.$filters['keyword'].'%');
            })
            ->with(['members:id,name_zh,name_fn,display_name'])
            ->orderByDesc('camp_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TrainingCamp $camp) => $this->serialize($camp))
            ->values();

        return Inertia::render('Organization/TrainingCamps', [
            'camps' => $camps,
            'members' => $this->memberOptions($organizationId),
            'filters' => $filters,
        ]);
    }

    /**
     * 新增一場集訓（含出席會員）。
     */
    public function store(Request $request)
    {
        $organizationId = session('organization')->id;
        $data = $this->validated($request);

        $this->ensureUnique($organizationId, $data);

        $camp = TrainingCamp::create([
            'organization_id' => $organizationId,
            'title' => $data['title'],
            'camp_date' => $data['camp_date'],
            'points' => (int) ($data['points'] ?? 0),
            'remark' => $data['remark'] ?? null,
        ]);

        $camp->members()->sync($this->memberIds($organizationId, $data['member_ids'] ?? []));

        return redirect()->back();
    }

    /**
     * 修改集訓資料與出席名單。
     */
    public function update(Request $request, TrainingCamp $trainingCamp)
    {
        $organizationId = session('organization')->id;
        $this->ensureOwned($trainingCamp, $organizationId);

        $data = $this->validated($request);

        $this->ensureUnique($organizationId, $data, $trainingCamp->id);

        $trainingCamp->update([
            'title' => $data['title'],
            'camp_date' => $data['camp_date'],
            'points' => (int) ($data['points'] ?? 0),
            'remark' => $data['remark'] ?? null,
        ]);

        $trainingCamp->members()->sync($this->memberIds($organizationId, $data['member_ids'] ?? []));

        return redirect()->back();
    }

    /**
     * 刪除集訓（出席記錄一併刪除）。
     */
    public function destroy(TrainingCamp $trainingCamp)
    {
        $this->ensureOwned($trainingCamp, session('organization')->id);

        $trainingCamp->delete();

        return redirect()->back();
    }

    /**
     * 從 Excel 匯入出席名單。回傳 { imported, errors }。
     *
     * 範本：public/templates/training_camp_list.xlsx
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new TrainingCampImport(session('organization'));

        Excel::import($import, $request->file('file'));

        return response()->json([
            'imported' => $import->importedCount(),
            'errors' => $import->failures()->values(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'camp_date' => 'required|date',
            'points' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string|max:255',
            'member_ids' => 'array',
            'member_ids.*' => 'integer',
        ]);
    }

    /**
     * 同一天不能有同名集訓。
     */
    private function ensureUnique(int $organizationId, array $data, ?int $ignoreId = null): void
    {
        $exists = TrainingCamp::where('organization_id', $organizationId)
            ->whereDate('camp_date', $data['camp_date'])
            ->where('title', $data['title'])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'title' => '同一天已有相同名稱的集訓',
            ]);
        }
    }

    /**
     * 只接受屬於本屬會的會員。
     */
    private function memberIds(int $organizationId, array $memberIds): array
    {
        if ($memberIds === []) {
            return [];
        }

        return Organization::find($organizationId)
            ->members()
            ->whereIn('members.id', $memberIds)
            ->pluck('members.id')
            ->all();
    }

    private function memberOptions(int $organizationId)
    {
        return Organization::find($organizationId)
            ->members
            ->map(fn ($member) => [
                'value' => $member->id,
                'label' => trim(
                    ($member->display_name ?: ($member->name_zh ?: $member->name_fn))
                    .' ('.$member->member_number.')'
                ),
            ])
            ->sortBy('label')
            ->values();
    }

    private function serialize(TrainingCamp $camp): array
    {
        $members = $camp->members
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->display_name ?: ($member->name_zh ?: $member->name_fn),
                'member_number' => $member->member_number,
            ])
            ->values();

        return [
            'id' => $camp->id,
            'title' => $camp->title,
            'camp_date' => optional($camp->camp_date)->format('Y-m-d'),
            'points' => (int) $camp->points,
            'remark' => $camp->remark,
            'member_ids' => $members->pluck('id')->all(),
            'member_count' => $members->count(),
            'total_points' => $members->count() * (int) $camp->points,
            'members' => $members,
        ];
    }

    /**
     * 篩選條件（預設近兩年）。
     */
    private function resolveFilters(Request $request): array
    {
        $end = $request->input('end') ?: Carbon::today()->format('Y-m-d');
        $start = $request->input('start')
            ?: Carbon::parse($end)->subYears(2)->addDay()->format('Y-m-d');

        return [
            'start' => $start,
            'end' => $end,
            'keyword' => trim((string) $request->input('keyword', '')),
        ];
    }

    private function ensureOwned(TrainingCamp $camp, int $organizationId): void
    {
        abort_if((int) $camp->organization_id !== (int) $organizationId, 403);
    }
}
