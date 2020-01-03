<?php

namespace App\Http\Controllers\Admin\System;

use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\jsonResponse;
use Illuminate\Http\Response;
use Exception;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $searParams = request()->all();
        $pageIndex = isset($searParams['pi']) ? intval($searParams['pi']) : 1;
        $pageSize = isset($searParams['ps']) ? intval($searParams['ps']) : 10;

        $query = Permission::query();
        isset($searParams['keywords']) && $query->where('name', 'like', '%' . trim($searParams['keywords']) . '%');

        $list = $query->paginate($pageSize, '*', 'pi', $pageIndex);

        return response()->json([
            'message' => '资源列表获取成功！',
            'data' => $list
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return jsonResponse
     */
    public function store(Request $request)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'name' => 'required|string',
            'route_name' => 'required',
        ], [
            'name.required' => '名称必填！',
            'route_name.required' => '路由名称必填！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = new Permission($requestData);
        if (!$instance->save()) {
            return response()->json([
                'message' => '资源创建失败！'
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
        $info = Permission::query()->findOrFail($id);

        return response()->json([
            'data' => $info,
        ]);
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
        $requestData = $request->except('id');
        $validator = validator($requestData, [
            'name' => 'required',
            'request_method' => 'required',
            'route_name' => 'required',
        ], [
            'name.required' => '名称必填！',
            'request_method.required' => '请求方法必填！',
            'route_name.required' => '路由名称必填！',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }
        if (!Permission::query()->findOrFail($id)->update($requestData)) {
            return response()->json([
                'message' => '资源更新失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => '资源更新成功！']);
    }

    /**
     * Remove the specified resource from storage.
     * @param $id
     * @return jsonResponse
     * @throws Exception
     */
    public function destroy($id)
    {
        if (!Permission::destroy($id)) {
            return response()->json([
                'message' => '资源删除失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => '资源删除成功！']);
    }

    /**
     * 获取所有权限
     *
     * @return JsonResponse
     */
    public function all()
    {
        $permissions = Permission::all();

        return \response()->json([
            'message' => '',
            'data' => $permissions,
        ]);
    }
}
