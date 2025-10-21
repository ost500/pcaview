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
            $contents = Contents::with('department')
                ->whereIn('department_id', $subscribedDepartmentIds)
                ->latest('published_at')
                ->paginate(20);
        } else {
            // 비로그인 사용자는 모든 콘텐츠 표시
            $contents = Contents::with('department')
                ->latest('published_at')
                ->paginate(20);
            $subscribedDepartmentIds = collect();
        }

        $churches = Church::all();
        $departments = Department::all();

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
}
