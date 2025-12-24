<?php

namespace App\Http\Controllers;

use App\Models\Contents;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FeedController extends Controller
{
    /**
     * 피드 게시물 저장
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'department_id' => 'required|exists:departments,id',
            'images.*' => 'nullable|image|max:10240', // 10MB
        ]);

        $user = Auth::user();

        // 요청받은 Department 사용
        $feedDepartment = Department::findOrFail($request->department_id);

        // 이미지 업로드 처리
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('feed-images', 's3');
                $imageUrls[] = Storage::disk('s3')->url($path);
            }
        }

        // Contents 생성
        $content = Contents::create([
            'department_id' => $feedDepartment->id,
            'type' => 'html',
            'title' => $request->get('content'),
            'body' => $request->get('content'),
            'file_url' => null,
            'thumbnail_url' => $imageUrls[0] ?? null,
            'published_at' => now(),
        ]);

        // 여러 이미지가 있는 경우 ContentsImage에 저장
        if (count($imageUrls) > 0) {
            foreach ($imageUrls as $index => $imageUrl) {
                $content->images()->create([
                    'page' => $index,
                    'file_url' => $imageUrl,
                ]);
            }
        }

        return redirect()->back()->with('success', '게시물이 작성되었습니다.');
    }
}
