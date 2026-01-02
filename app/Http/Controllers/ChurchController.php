<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\Contents;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChurchController extends Controller
{
    public function index()
    {
        $churches = Church::with('departments')->get();

        return Inertia::render('church/Index', ['churches' => $churches]);
    }

    public function show(Request $request, int $id)
    {
        $church = Church::with('departments')->findOrFail($id);

        // 해당 교회의 부서들
        $departments = $church->departments()->latest()->get();

        // 해당 교회의 모든 콘텐츠 가져오기 (church_id로 필터링)
        $contents = Contents::with(['department', 'departments'])
            ->withCount('comments')
            ->where('church_id', $church->id)
            ->latest('published_at')
            ->paginate(20);

        return Inertia::render('church/Show', [
            'church' => $church,
            'departments' => $departments,
            'contents' => $contents,
        ]);
    }
}
