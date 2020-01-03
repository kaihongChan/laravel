<?php

namespace App\Http\Controllers\Admin\System;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Illuminate\Http\JsonResponse;

class AppController extends Controller
{
    /**
     * 应用初始化数据
     * @return JsonResponse
     */
    public function initialization()
    {
        return response()->json([
            'message' => '资源获取成功！',
            'data' => [
                'name' => 'Laravel',
                'description' => 'Laravel Project.',
                'menu' => [],
            ],

        ]);
    }
}
