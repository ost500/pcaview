<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdClick;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SymlinkController extends Controller
{
    #[OA\Get(
        path: '/api/live/count',
        summary: 'Track ad click and redirect to target URL',
        tags: ['Ad Tracking'],
        parameters: [
            new OA\Parameter(
                name: 'ad_id',
                in: 'query',
                required: true,
                description: 'Advertising ID to track',
                schema: new OA\Schema(type: 'string', example: 'coupang_partner_001')
            ),
        ],
        responses: [
            new OA\Response(
                response: 302,
                description: 'Redirect to target URL after tracking click',
            ),
            new OA\Response(
                response: 400,
                description: 'Ad ID is required',
            ),
            new OA\Response(
                response: 404,
                description: 'Ad ID not found',
            ),
        ]
    )]
    public function count(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ad_id' => 'required|string|max:255',
        ]);

        $adId = $validated['ad_id'];

        // Find ad click record by ad_id
        $adClick = AdClick::where('ad_id', $adId)->first();

        if (!$adClick) {
            abort(404, 'Ad ID not found');
        }

        // Increment click count
        $adClick->incrementClickCount();

        // Redirect to target URL
        return redirect($adClick->redirect_url);
    }
}
