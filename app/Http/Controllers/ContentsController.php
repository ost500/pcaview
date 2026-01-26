<?php

namespace App\Http\Controllers;

use App\Models\Contents;
use App\Services\ContentsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContentsController extends Controller
{
    public function show(int $id)
    {
        $contents = Contents::with('user', 'church', 'images', 'department', 'comments.user', 'tags')->findOrFail($id);

        // 중복 제거를 위한 서브쿼리
        $uniqueContentsQuery = \DB::table('contents')
            ->select(\DB::raw('MAX(id) as id'))
            ->where('id', '!=', $id)
            ->groupBy('department_id', 'title');

        // department가 있는 경우 해당 department의 콘텐츠
        if ($contents->department_id) {
            $uniqueContentsIds = \DB::table('contents')
                ->join('content_department', 'contents.id', '=', 'content_department.content_id')
                ->where('content_department.department_id', $contents->department_id)
                ->where('contents.id', '!=', $id)
                ->select(\DB::raw('MAX(contents.id) as id'))
                ->groupBy('contents.department_id', 'contents.title')
                ->pluck('id');
        } else {
            // department가 없는 경우 같은 church의 콘텐츠
            $uniqueContentsIds = \DB::table('contents')
                ->where('church_id', $contents->church_id)
                ->where('id', '!=', $id)
                ->select(\DB::raw('MAX(id) as id'))
                ->groupBy('department_id', 'title')
                ->pluck('id');
        }

        // 같은 department의 다른 콘텐츠 가져오기 (최대 10개, hidden 제외)
        $relatedContents = Contents::with(['user', 'church', 'department', 'departments'])
            ->withCount('comments')
            ->whereIn('id', $uniqueContentsIds)
            ->where('is_hide', false)
            ->latest('published_at')
            ->take(10)
            ->get();

        return Inertia::render('contents/Show', [
            'contents' => $contents,
            'relatedContents' => $relatedContents,
        ]);
    }

    // 모바일 전용 콘텐츠 상세 페이지 (헤더 없음)
    public function mobileShow(int $id)
    {
        $contents = Contents::with('user', 'church', 'images', 'department', 'comments.user', 'tags')->findOrFail($id);

        // 중복 제거를 위한 서브쿼리
        $uniqueContentsQuery = \DB::table('contents')
            ->select(\DB::raw('MAX(id) as id'))
            ->where('id', '!=', $id)
            ->groupBy('department_id', 'title');

        // department가 있는 경우 해당 department의 콘텐츠
        if ($contents->department_id) {
            $uniqueContentsIds = \DB::table('contents')
                ->join('content_department', 'contents.id', '=', 'content_department.content_id')
                ->where('content_department.department_id', $contents->department_id)
                ->where('contents.id', '!=', $id)
                ->select(\DB::raw('MAX(contents.id) as id'))
                ->groupBy('contents.department_id', 'contents.title')
                ->pluck('id');
        } else {
            // department가 없는 경우 같은 church의 콘텐츠
            $uniqueContentsIds = \DB::table('contents')
                ->where('church_id', $contents->church_id)
                ->where('id', '!=', $id)
                ->select(\DB::raw('MAX(id) as id'))
                ->groupBy('department_id', 'title')
                ->pluck('id');
        }

        // 같은 department의 다른 콘텐츠 가져오기 (최대 10개, hidden 제외)
        $relatedContents = Contents::with(['user', 'church', 'department', 'departments'])
            ->withCount('comments')
            ->whereIn('id', $uniqueContentsIds)
            ->where('is_hide', false)
            ->latest('published_at')
            ->take(10)
            ->get();

        return Inertia::render('mobile/contents/Show', [
            'contents' => $contents,
            'relatedContents' => $relatedContents,
        ]);
    }

    /**
     * 콘텐츠 삭제
     */
    public function destroy(Request $request, int $id, ContentsService $contentsService)
    {
        $contents = Contents::with('church', 'images')->findOrFail($id);

        try {
            // 삭제 후 돌아갈 URL 저장 (church 페이지)
            $redirectUrl = $contentsService->getRedirectUrlAfterDelete($contents, url()->previous());

            // 콘텐츠 삭제
            $contentsService->deleteContents($contents, $request->user());

            return redirect($redirectUrl)->with('success', '콘텐츠가 삭제되었습니다.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
