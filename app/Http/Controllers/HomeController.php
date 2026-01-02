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

        // 중복 제거를 위한 서브쿼리 (department_id, title로 그룹화하여 최신 id만 선택)
        $uniqueContentsIds = \DB::table('contents')
            ->select(\DB::raw('MAX(id) as id'))
            ->groupBy('department_id', 'title')
            ->pluck('id');

        // 로그인한 사용자의 경우 구독한 부서의 콘텐츠만 표시
        if ($user) {
            // user_departments는 구독 안 하는 부서를 저장
            $unsubscribedDepartmentIds = $user->departments()->pluck('departments.id');

            // 전체 부서 - 구독 안 하는 부서 = 구독하는 부서
            $allDepartmentIds = Department::pluck('id');
            $subscribedDepartmentIds = $allDepartmentIds->diff($unsubscribedDepartmentIds);

            $contents = Contents::with(['user', 'church', 'department', 'departments'])
                ->withCount('comments')
                ->whereIn('id', $uniqueContentsIds)
                ->whereHas('departments', function ($query) use ($subscribedDepartmentIds) {
                    $query->whereIn('departments.id', $subscribedDepartmentIds);
                })
                ->latest('published_at')
                ->paginate(20);
        } else {
            // 비로그인 사용자는 모든 콘텐츠 표시
            $contents = Contents::with(['user', 'church', 'department', 'departments'])
                ->withCount('comments')
                ->whereIn('id', $uniqueContentsIds)
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
            // user_departments는 구독 안 하는 부서를 저장
            $unsubscribedDepartmentIds = $user->departments()
                ->whereIn('departments.id', $departmentIds)
                ->pluck('departments.id');

            // 해당 교회의 부서 중 구독하는 부서
            $subscribedDepartmentIds = $departmentIds->diff($unsubscribedDepartmentIds);
        } else {
            $subscribedDepartmentIds = collect();
        }

        // 중복 제거를 위한 서브쿼리
        $uniqueContentsIds = \DB::table('contents')
            ->select(\DB::raw('MAX(id) as id'))
            ->where('church_id', $church->id)
            ->groupBy('department_id', 'title')
            ->pluck('id');

        // Church 페이지에서는 해당 교회의 모든 콘텐츠를 표시
        $contents = Contents::with(['user', 'church', 'department', 'departments'])
            ->withCount('comments')
            ->whereIn('id', $uniqueContentsIds)
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
