<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestApiController extends Controller
{
    public function test()
    {
        return response()->json([
            'ok' => true,
            'message' => 'API работает!',
            'timestamp' => now()->toISOString(),
            'data' => [
                'test' => 'success',
                'version' => '1.0'
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    
    public function testParams(Request $request)
    {
        return response()->json([
            'ok' => true,
            'params' => $request->all(),
            'headers' => $request->headers->all()
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
