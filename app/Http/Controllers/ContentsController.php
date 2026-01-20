<?php

namespace App\Http\Controllers;

use App\Models\Contents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
    public function destroy(Request $request, int $id)
    {
        $contents = Contents::with('church')->findOrFail($id);

        // 권한 확인: 콘텐츠 작성자만 삭제 가능
        if ($contents->user_id !== $request->user()->id) {
            return back()->with('error', '삭제 권한이 없습니다.');
        }

        // 삭제 후 돌아갈 URL 저장 (church 페이지)
        $redirectUrl = $contents->church && $contents->church->slug
            ? '/c/' . $contents->church->slug
            : url()->previous();

        // 관련 이미지 삭제
        if ($contents->images) {
            foreach ($contents->images as $image) {
                // S3에서 이미지 삭제
                if ($image->url) {
                    $path = parse_url($image->url, PHP_URL_PATH);
                    if ($path && Storage::disk('s3')->exists(ltrim($path, '/'))) {
                        Storage::disk('s3')->delete(ltrim($path, '/'));
                    }
                }
                // 이미지 레코드 삭제
                $image->delete();
            }
        }

        // 콘텐츠 삭제 (연관된 댓글, 태그 등은 모델의 cascade 설정에 따라 처리)
        $contents->delete();

        return redirect($redirectUrl)->with('success', '콘텐츠가 삭제되었습니다.');
    }
}
