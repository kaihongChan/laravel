<?php

namespace App\Http\Controllers\Admin\System;

use App\Models\Policy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class PolicyController extends Controller
{
    protected $policy;

    public function __construct(Policy $policy)
    {
        $this->policy = $policy;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $pageIndex = intval(\request()->get('pi', 1));
        $pageSize = intval(\request()->get('ps', 10));
        $keywords = trim(\request('keywords'));
        $query = $this->policy::query();
        $keywords && $query->where('name', 'like', '%' . $keywords . '%');
        $list = $query->with('permissions')->paginate($pageSize, ['*'], 'pi', $pageIndex);

        return response()->json([
            'message' => '资源列表获取成功！',
            'data' => $list
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'name' => 'required',
            'identify' => 'required|unique:policies',
            'permissions' => 'required|array',
        ], [
            'name.required' => '名称必填！',
            'identify.required' => '唯一标识必填！',
            'identify.unique' => '唯一标识唯一！',
            'permissions.required' => '权限必填！',
            'permissions.array' => '权限格式错误！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->policy->editOrAdd($requestData)) {
            return response()->json([
                'message' => $this->policy->getError(),
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
    public function show($id): JsonResponse
    {
        $info = $this->policy::query()->with('permissions')->findOrFail($id);
        return response()->json([
            'message' => '资源获取成功！',
            'data' => $info
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'name' => 'required',
            'identify' => 'required|unique:policies,id,' . $id,
            'permissions' => 'required|array',
        ], [
            'name.required' => '名称必填！',
            'identify.required' => '唯一标识必填！',
            'identify.unique' => '唯一标识唯一！',
            'permissions.required' => '权限必填！',
            'permissions.array' => '权限格式错误！',
        ]);
        if ($validator->fails()) {
            return \response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->policy->editOrAdd($requestData, $id)) {
            return response()->json([
                'message' => $this->policy->getError(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => '资源更新成功！']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        if (!$this->policy::destroy($id)) {
            return response()->json([
                'message' => '资源删除失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([]);
    }

    /**
     * 获取全部
     *
     * @return JsonResponse
     */
    public function all()
    {
        $policies = $this->policy::all();

        return \response()->json([
            'message' => '',
            'data' => $policies,
        ]);
    }
}
