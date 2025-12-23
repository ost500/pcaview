<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Contents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, Contents $content)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
            'guest_name' => 'nullable|string|max:50',
        ]);

        $data = [
            'content_id' => $content->id,
            'body' => $validated['body'],
            'ip_address' => $request->ip(),
        ];

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        } else {
            $data['guest_name'] = $validated['guest_name'] ?? '익명';
        }

        $comment = Comment::create($data);
        $comment->load('user');

        return redirect()->back()->with('success', '댓글이 등록되었습니다.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        // 로그인한 사용자의 경우 본인이 작성한 댓글만 삭제 가능
        if ($comment->user_id && Auth::check()) {
            if ($comment->user_id !== Auth::id()) {
                return redirect()->back()->with('error', '권한이 없습니다.');
            }
        } else {
            // 비로그인 댓글은 같은 IP만 삭제 가능
            if ($comment->ip_address !== $request->ip()) {
                return redirect()->back()->with('error', '권한이 없습니다.');
            }
        }

        $comment->delete();

        return redirect()->back()->with('success', '댓글이 삭제되었습니다.');
    }
}
