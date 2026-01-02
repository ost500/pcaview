<?php

namespace App\Http\Controllers;

use App\Models\Contents;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'church_id' => 'nullable|exists:churches,id',
            'department_id' => 'nullable|exists:departments,id',
            'images.*' => 'nullable|image|max:10240', // 10MB
        ]);

        // church_id와 department_id 중 하나는 반드시 있어야 함
        if (!$request->church_id && !$request->department_id) {
            return redirect()->back()->withErrors(['error' => '교회 또는 부서를 선택해주세요.']);
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

        // Church mode: church에 content 생성하고 모든 department와 연결
        if ($request->church_id) {
            $church = \App\Models\Church::findOrFail($request->church_id);
            $departments = $church->departments;

            if ($departments->isEmpty()) {
                return redirect()->back()->withErrors(['error' => '해당 교회에 부서가 없습니다.']);
            }

            DB::transaction(function () use ($church, $departments, $request, $imageUrls, $user) {
                // 하나의 content만 생성 (교회의 대표 department 사용)
                $primaryDepartment = $church->primaryDepartment ?? $departments->first();

                $content = Contents::create([
                    'user_id' => $user->id,
                    'church_id' => $church->id,
                    'department_id' => $primaryDepartment->id, // 교회의 대표 department 설정
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

                // 모든 department와 연결 (pivot table)
                $content->departments()->attach($departments->pluck('id'));
            });

            return redirect()->back()->with('success', '모든 부서에 게시물이 작성되었습니다.');
        }

        // Department mode: 선택된 department에만 content 생성
        $feedDepartment = Department::findOrFail($request->department_id);

        DB::transaction(function () use ($feedDepartment, $request, $imageUrls, $user) {
            $content = Contents::create([
                'user_id' => $user->id,
                'church_id' => $feedDepartment->church_id, // department의 church_id 사용
                'department_id' => $feedDepartment->id, // 대표 department 설정
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

            // 선택된 department와 연결 (pivot table)
            $content->departments()->attach($feedDepartment->id);
        });

        return redirect()->back()->with('success', '게시물이 작성되었습니다.');
    }
}
