<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Contents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * 특정 콘텐츠의 댓글 목록 조회
     */
    public function index(Request $request, $contentId)
    {
        $content = Contents::findOrFail($contentId);

        $comments = Comment::with('user')
            ->where('content_id', $contentId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($comments);
    }

    /**
     * 댓글 작성 (인증 사용자 + 게스트)
     */
    public function store(Request $request, $contentId)
    {
        $content = Contents::findOrFail($contentId);

        $validatedData = $request->validate([
            'body' => 'required|string|max:1000',
            'guest_name' => 'required_without:user_id|nullable|string|max:50',
        ]);

        $user = Auth::user();

        $comment = Comment::create([
            'content_id' => $contentId,
            'user_id' => $user?->id,
            'guest_name' => $user ? null : $validatedData['guest_name'],
            'ip_address' => $request->ip(),
            'body' => $validatedData['body'],
        ]);

        $comment->load('user');

        return response()->json([
            'success' => true,
            'message' => '댓글이 작성되었습니다.',
            'comment' => $comment,
        ], 201);
    }

    /**
     * 댓글 삭제 (본인만 가능)
     */
    public function destroy(Request $request, $contentId, $commentId)
    {
        $comment = Comment::where('content_id', $contentId)
            ->where('id', $commentId)
            ->firstOrFail();

        $user = Auth::user();

        // 권한 체크: 로그인 사용자는 본인 댓글만, 게스트는 같은 IP + 같은 이름
        if ($comment->user_id) {
            // 인증된 사용자의 댓글
            if (!$user || $user->id !== $comment->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => '본인의 댓글만 삭제할 수 있습니다.',
                ], 403);
            }
        } else {
            // 게스트 댓글
            $guestName = $request->input('guest_name');
            if ($comment->guest_name !== $guestName || $comment->ip_address !== $request->ip()) {
                return response()->json([
                    'success' => false,
                    'message' => '게스트 댓글은 작성자만 삭제할 수 있습니다.',
                ], 403);
            }
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => '댓글이 삭제되었습니다.',
        ]);
    }
}
