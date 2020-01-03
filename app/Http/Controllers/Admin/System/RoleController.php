<?php

namespace App\Http\Controllers\Admin\System;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    /**
     * role模型
     *
     * @var Role
     */
    protected $role;

    /**
     * 构造函数
     *
     * RoleController constructor.
     * @param Role $role
     */
    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $pageIndex = intval(\request()->get('pi', 1));
        $pageSize = intval(\request()->get('ps', 10));

        $searParams = \request()->except(['pi', 'ps']);
        $query = $this->role::query();
        isset($searParams['keywords']) &&
        $query->where('name', 'like', '%' . trim($searParams['keywords']) . '%');

        $list = $query->paginate($pageSize, ['*'], 'pi', $pageIndex);

        return response()->json([
            'message' => '',
            'data' => $list
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
            'identify' => 'required|unique:roles',
            'name' => 'required',
            'policies' => 'required|array',
            'menus' => 'required|array',
        ], [
            'identify.required' => '唯一标识必填！',
            'identify.unique' => '角色标识唯一！',
            'name.required' => '名称必填！',
            'policies.required' => '权限策略必填！',
            'menus.required' => '菜单必填！',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->role->editOrAdd($requestData)) {
            return response()->json([
                'message' => $this->role->getError(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源创建成功！'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $info = $this->role::query()->with(['menus', 'policies'])->findOrFail($id)->toArray();

        $menus = $info['menus'];
        $idAsKeyMenus = [];
        foreach ($menus as $menu) {
            $menu['key'] = $menu['id'];
            $menu['title'] = $menu['name'];
            $menu['children'] = [];
            $idAsKeyMenus[$menu['id']] = $menu;
        }

        $info['menus'] = formatMenuTree($idAsKeyMenus);
        return response()->json([
            'message' => '',
            'data' => $info
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(Request $request, $id)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'identify' => 'required|unique:roles,id,' . $id,
            'name' => 'required',
            'policies' => 'required|array',
            'menus' => 'required|array',
        ], [
            'identify.required' => '唯一标识必填！',
            'identify.unique' => '角色标识唯一！',
            'name.required' => '名称必填！',
            'policies.required' => '权限策略必填！',
            'menus.required' => '菜单必填！',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->role->editOrAdd($requestData, $id)) {
            return response()->json([
                'message' => $this->role->getError(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源更新成功！'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!$this->role::destroy($id)) {
            response()->json([
                'message' => '资源删除失败！'
            ]);
        }

        return response()->json([
            'message' => '资源删除成功！'
        ]);
    }

    /**
     * 角色获取
     *
     * @return JsonResponse
     */
    public function all()
    {
        $roles = $this->role::all();

        return response()->json([
            'message' => '资源获取成功！',
            'data' => $roles
        ]);
    }
}
