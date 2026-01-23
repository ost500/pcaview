<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contents;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Church; // Added for Church model

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $contents = Contents::with(['user', 'church', 'departments', 'images'])
            ->orderBy('published_at', 'desc')
            ->paginate(15);

        return response()->json($contents);
    }

    /**
     * 피드 게시물 저장 (API용)
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'nullable|string|max:5000',
            'church_id' => 'nullable|exists:churches,id',
            'department_id' => 'nullable|exists:departments,id',
            'images.*' => 'nullable|image|max:10240', // 10MB
            'video' => 'nullable|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/x-matroska|max:512000', // 500MB
        ]);

        // church_id와 department_id 중 하나는 반드시 있어야 함
        if (!$request->church_id && !$request->department_id) {
            return response()->json(['success' => false, 'message' => '교회 또는 부서를 선택해주세요.'], 400);
        }

        // content, 이미지, 동영상 중 최소 하나는 있어야 함
        if (!$request->filled('content') && !$request->hasFile('images') && !$request->hasFile('video')) {
            return response()->json(['success' => false, 'message' => '내용, 사진, 또는 동영상 중 하나는 필수입니다.'], 400);
        }

        $user = Auth::user();

        // 이미지 업로드 처리
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('feed-images', 's3');
                $imageUrls[] = Storage::disk('s3')->url($path);
            }
        }

        // 동영상 업로드 처리
        $videoUrl = null;
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('feed-videos', 's3');
            $videoUrl = Storage::disk('s3')->url($path);
        }

        $content = null; // Declare $content outside the transaction

        // Church mode: church에 content 생성하고 모든 department와 연결
        if ($request->church_id) {
            $church = Church::findOrFail($request->church_id);
            $departments = $church->departments;

            DB::transaction(function () use ($church, $departments, $request, $imageUrls, $videoUrl, $user, &$content) { // Pass $content by reference
                // 하나의 content만 생성 (church 모드에서는 department_id를 null로 설정)
                $content = Contents::create([
                    'user_id' => $user->id,
                    'church_id' => $church->id,
                    'department_id' => null, // church에서 작성한 경우 null
                    'type' => 'html',
                    'title' => $request->get('content', ''),
                    'body' => $request->get('content', ''),
                    'file_url' => null,
                    'thumbnail_url' => $imageUrls[0] ?? null,
                    'video_url' => $videoUrl,
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

                // 모든 department와 연결 (pivot table)
                $content->departments()->attach($departments->pluck('id'));
            });

            return response()->json(['success' => true, 'message' => '모든 부서에 게시물이 작성되었습니다.', 'content' => $content], 201);
        }

        // Department mode: 선택된 department에만 content 생성
        $feedDepartment = Department::findOrFail($request->department_id);

        DB::transaction(function () use ($feedDepartment, $request, $imageUrls, $videoUrl, $user, &$content) { // Pass $content by reference
            $content = Contents::create([
                'user_id' => $user->id,
                'church_id' => $feedDepartment->church_id, // department의 church_id 사용
                'department_id' => $feedDepartment->id, // 대표 department 설정
                'type' => 'html',
                'title' => $request->get('content', ''),
                'body' => $request->get('content', ''),
                'file_url' => null,
                'thumbnail_url' => $imageUrls[0] ?? null,
                'video_url' => $videoUrl,
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

            // 선택된 department와 연결 (pivot table)
            $content->departments()->attach($feedDepartment->id);
        });

        return response()->json(['success' => true, 'message' => '게시물이 작성되었습니다.', 'content' => $content], 201);
    }
}