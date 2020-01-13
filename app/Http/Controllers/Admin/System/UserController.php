<?php

namespace App\Http\Controllers\Admin\System;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{

    /**
     * @var User
     */
    protected $user;

    /**
     * UserController constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * 资源列表
     *
     * @return JsonResponse
     */
    public function index()
    {
        $pageIndex = request()->get('pi', 1);
        $pageSize = request()->get('ps', 10);
        $keywords = request()->get('keywords');

        $query = $this->user::query();
        $keywords && $query->where('name', 'like', '%' . $keywords . '%')
            ->orWhere('nickname', 'like', '%' . $keywords . '%');

        $list = $query->paginate($pageSize, '*', 'pi', $pageIndex);

        return response()->json([
            'message' => '资源列表获取成功！',
            'data' => $list
        ]);
    }

    /**
     * 资源创建
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'name' => 'required|unique:users',
            'nickname' => 'required',
            'email' => 'required',
            'password' => 'required',
            'checkPassword' => 'required',
        ], [
            'name.required' => '名称必填！',
            'nickname.required' => '昵称必填！',
            'email.required' => '邮箱必填！',
            'password.required' => '密码必填！',
            'checkPassword.required' => '确认密码必填！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->user->editOrAdd($requestData)) {
            return response()->json([
                'message' => $this->user->getError(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => '资源创建成功！']);
    }

    /**
     * 资源详情
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $info = $this->user::query()->with([
            'roles:id,name,identify',
            'departments:id,name'
        ])->findOrFail($id);

        return response()->json([
            'message' => '资源获取成功！',
            'data' => $info
        ]);
    }

    /**
     * 资源更新
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'name' => 'required|unique:users,id,' . $id,
            'nickname' => 'required',
            'email' => 'required',
        ], [
            'name.required' => '名称必填！',
            'nickname.required' => '昵称必填！',
            'email.required' => '邮箱必填！',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->user->editOrAdd($requestData, $id)) {
            return response()->json([
                'message' => $this->user->getError(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => '资源更新成功！']);
    }

    /**
     * 资源删除
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        // TODO:超级管理员禁止删除
        if (!$this->user::destroy($id)) {
            return response()->json([
                'message' => '资源删除失败'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源删除成功！'
        ]);
    }

    /**
     * 列表
     * @return JsonResponse
     */
    public function all()
    {
        $list = $this->user::all(['id', 'name', 'nickname']);

        return response()->json([
            'message' => '资源列表获取成功！',
            'data' => $list
        ]);
    }
}
