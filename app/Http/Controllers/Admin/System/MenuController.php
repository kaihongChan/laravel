<?php

namespace App\Http\Controllers\Admin\System;

use App\Models\Menu;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    /**
     * 模型
     *
     * @var Menu
     */
    protected $menu;

    /**
     * 构造函数
     *
     * @param Menu $menu
     */
    public function __construct(Menu $menu)
    {
        $this->menu = $menu;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $menus = Menu::all();
        // 格式化为以id为键值的数组
        $idAsKey = [];
        foreach ($menus->toArray() as $menu) {
            $menu['key'] = $menu['id'];
            $menu['title'] = $menu['name'];
            $menu['children'] = [];
            $idAsKey[$menu['id']] = $menu;
        }
        return response()->json([
            'message' => '',
            'data' => formatMenuTree($idAsKey),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'name' => 'required|string',
            'i18n' => 'required',
            'link' => 'required_if:type,1',
        ], [
            'name.required' => '名称必填！',
            'i18n.required' => '请求方法必填！',
            'link.required_if' => '链接必填！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->menu->editOrAdd($requestData)) {
            return \response()->json([
                'message' => $this->menu->getError(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源创建成功！'
        ], Response::HTTP_CREATED);

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $info = Menu::query()->with(['parent', 'roles'])->findOrFail($id);

        return response()->json([
            'message' => '',
            'data' => $info]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'name' => 'required|string',
            'i18n' => 'required',
            'link' => 'required_if:type,1',
        ], [
            'name.required' => '名称必填！',
            'i18n.required' => '请求方法必填！',
            'link.required_if' => '链接必填！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->menu->editOrAdd($requestData, $id)) {
            return \response()->json([
                'message' => $this->menu->getError(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源更新成功！'
        ], Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $instance = Menu::query()->whereHas('children')->where('id', $id)->get();
        if (!empty($instance->toArray())) {
            return response()->json([
                'message' => '请先删除子级！'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!Menu::destroy($id)) {
            return response()->json([
                'message' => '资源删除失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源删除成功！'
        ]);
    }


}
