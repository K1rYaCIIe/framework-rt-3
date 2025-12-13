<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CmsApiController extends Controller
{
    public function getBlock($slug)
    {
        try {
            $block = DB::selectOne(
                "SELECT content FROM cms_blocks WHERE slug = ? AND is_active = TRUE LIMIT 1",
                [$slug]
            );
            
            if ($block) {
                return response()->json([
                    'ok' => true,
                    'content' => $block->content
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }
            
            return response()->json([
                'ok' => false,
                'error' => 'Block not found'
            ], 404, [], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => 'Database error',
                'message' => $e->getMessage()
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
