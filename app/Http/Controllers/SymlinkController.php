<?php

namespace App\Http\Controllers;

use App\Models\SymlinkVisit;
use Illuminate\Http\Request;

class SymlinkController extends Controller
{
    /**
     * Track symlink visit
     */
    public function track(Request $request)
    {
        // 방문 기록 저장 (ad_id 중복 시 업데이트)
        SymlinkVisit::updateOrCreate(
            ['ad_id' => $request->input('ad_id')],
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
            ]
        );

        // 홈으로 리다이렉트
        return [
            'initial' => 60,
            'interval' => 180,
        ];
    }
}
