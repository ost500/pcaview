<?php

namespace App\Http\Controllers;

use App\Models\Contents;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContentsController extends Controller
{
    public function show(int $id)
    {
        $contents = Contents::with('user', 'church', 'images', 'department', 'comments.user', 'tags')->findOrFail($id);

        // 같은 department의 다른 콘텐츠 가져오기 (최대 10개)
        $relatedContents = Contents::with(['user', 'church', 'department', 'departments'])
            ->withCount('comments')
            ->where('id', '!=', $id);

        // department가 있는 경우 해당 department의 콘텐츠
        if ($contents->department_id) {
            $relatedContents = $relatedContents->whereHas('departments', function ($query) use ($contents) {
                $query->where('departments.id', $contents->department_id);
            });
        } else {
            // department가 없는 경우 같은 church의 콘텐츠
            $relatedContents = $relatedContents->where('church_id', $contents->church_id);
        }

        $relatedContents = $relatedContents->latest('published_at')->take(10)->get();

        return Inertia::render('contents/Show', [
            'contents' => $contents,
            'relatedContents' => $relatedContents,
        ]);
    }
}
