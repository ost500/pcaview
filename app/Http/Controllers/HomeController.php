<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\Contents;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 로그인한 사용자의 경우 구독한 부서의 콘텐츠만 표시
        if ($user) {
            $subscribedDepartmentIds = $user->departments()->pluck('departments.id');
            $contents = Contents::with(['department', 'departments'])
                ->withCount('comments')
                ->whereHas('departments', function ($query) use ($subscribedDepartmentIds) {
                    $query->whereIn('departments.id', $subscribedDepartmentIds);
                })
                ->latest('published_at')
                ->paginate(20);
        } else {
            // 비로그인 사용자는 모든 콘텐츠 표시
            $contents = Contents::with(['department', 'departments'])
                ->withCount('comments')
                ->latest('published_at')
                ->paginate(20);
            $subscribedDepartmentIds = collect();
        }

        $churches = Church::all();
        $departments = Department::latest()->take(30)->get();

        // For infinite scroll requests, only return contents data
        if ($request->wantsJson() || $request->header('X-Inertia-Partial-Data')) {
            return Inertia::render('Welcome', [
                'contents' => $contents,
                'churches' => $churches,
                'departments' => $departments,
                'subscribedDepartmentIds' => $subscribedDepartmentIds->toArray(),
            ]);
        }

        return Inertia::render('Welcome', [
            'contents' => $contents,
            'churches' => $churches,
            'departments' => $departments,
            'subscribedDepartmentIds' => $subscribedDepartmentIds->toArray(),
        ]);
    }

    public function church(Request $request, Church $church)
    {
        $user = $request->user();

        // 해당 교회의 부서들만 가져오기
        $departments = $church->departments()->latest()->get();
        $departmentIds = $departments->pluck('id');

        // 해당 교회의 콘텐츠 가져오기 (church_id로 필터링)
        if ($user) {
            $subscribedDepartmentIds = $user->departments()
                ->whereIn('departments.id', $departmentIds)
                ->pluck('departments.id');
        } else {
            $subscribedDepartmentIds = collect();
        }

        // Church 페이지에서는 해당 교회의 모든 콘텐츠를 표시
        $contents = Contents::with(['department', 'departments'])
            ->withCount('comments')
            ->where('church_id', $church->id)
            ->latest('published_at')
            ->paginate(20);

        $churches = Church::all();

        return Inertia::render('Welcome', [
            'contents' => $contents,
            'churches' => $churches,
            'departments' => $departments,
            'subscribedDepartmentIds' => $subscribedDepartmentIds->toArray(),
            'currentChurch' => $church,
        ]);
    }
}
