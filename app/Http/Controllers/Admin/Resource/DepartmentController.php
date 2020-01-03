<?php

namespace App\Http\Controllers\Admin\Resource;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $departments = Department::all();

        // 格式化为以id为键值的数组
        $formatDepartments = [];
        foreach ($departments->toArray() as $dept) {
            $dept['key'] = $dept['id'];
            $dept['title'] = $dept['name'];
            $dept['children'] = [];
            $formatDepartments[$dept['id']] = $dept;
        }

        return response()->json([
            'message' => '',
            'data' => formatMenuTree($formatDepartments),
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
            'name' => 'required',
        ], [
            'name.required' => '名称必填！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = new Department($requestData);
        if (!$instance->save()) {
            return response()->json([
                'message' => '资源创建失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源创建成功！',
        ]);
    }

    /**
     * 资源详情
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $info = Department::query()->findOrFail($id);

        return response()->json([
            'data' => $info,
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
            'name' => 'required',
        ], [
            'name.required' => '名称必填！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = Department::query()->findOrFail($id);
        if (!$instance->update($requestData)) {
            return response()->json([
                'message' => '资源创建失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源创建成功！',
        ]);
    }

    /**
     * 删除
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $instance = Department::query()->whereHas('children')->where('id', $id)->get();
        if (!empty($instance->toArray())) {
            return response()->json([
                'message' => '请先删除子级！'
            ], Response::HTTP_BAD_REQUEST);
        }
        if (!Department::destroy($id)) {
            return response()->json([
                'message' => '资源删除失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源删除成功！'
        ]);
    }

    /**
     * 部门成员
     * @return JsonResponse
     */
    public function members()
    {
        $id = \request()->get('id');
        $pageIndex = request()->get('pi', 1);
        $pageSize = request()->get('ps', 10);
        $keywords = \request()->get('keywords');
        $is_manager = \request()->get('is_manager', null);

        $query = DB::table('users')
            ->join('department_users', 'users.id', '=', 'department_users.user_id')
            ->where('department_users.dept_id', $id);

        $keywords && $query->where('users.name', 'like', '%' . $keywords . '%')
            ->orWhere('users.nickname', 'like', '%' . $keywords . '%');
        !is_null($is_manager) && $query->where('department_users.is_manager', $is_manager);

        $members = $query->paginate($pageSize, [
            'users.id', 'users.name', 'nickname', 'email', 'mobile', 'is_manager'
        ], 'pi', $pageIndex);

        return response()->json([
            'message' => '',
            'data' => $members,
        ]);
    }

    /**
     * 主管设置
     * @return JsonResponse
     */
    public function setManagers()
    {
        $id = \request()->post('id');

        $checkedMembers = \request()->post('managers');

        $result = DB::table('department_users')->where('dept_id', $id)
            ->whereIn('user_id', $checkedMembers)->update(['is_manager' => 1]);

        if (!$result) {
            return response()->json([
                'message' => '主管设置失败！'
            ], Response::HTTP_BAD_REQUEST);
        }
        return response()->json([
            'message' => '主管设置成功！'
        ]);
    }

    /**
     * 主管移除
     * @return JsonResponse
     */
    public function removeManagers()
    {
        $id = \request()->post('id');

        $checkedMembers = \request()->post('managers');

        $result = DB::table('department_users')->where('dept_id', $id)
            ->whereIn('user_id', $checkedMembers)->update(['is_manager' => 0]);

        if (!$result) {
            return response()->json([
                'message' => '主管移除失败！'
            ], Response::HTTP_BAD_REQUEST);
        }
        return response()->json([
            'message' => '主管移除成功！'
        ]);
    }
}
