<?php

namespace App\Http\Controllers\Admin\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class WorkflowController extends Controller
{
    /**
     * 资源列表
     *
     * @return JsonResponse
     */
    public function index()
    {
        $pageIndex = request()->input('pi', 1);
        $pageSize = request()->input('ps', 10);
        $keywords = request()->input('keywords');

        $query = Workflow::query();
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
            'model' => 'required|unique:workflow',
            'name' => 'required',
        ], [
            'model.required' => '适用模型必填！',
            'model.unique' => '适用模型唯一！',
            'name.required' => '名称必填！',
        ]);

        // 验证是否实现审核接口

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = new Workflow($requestData);
        if (!$instance->save()) {
            return response()->json([
                'message' => '资源创建失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源创建成功！'
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
        $info = Workflow::query()->findOrFail($id);

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
            'model' => 'required|unique:workflow,id,' . $id,
            'name' => 'required',
        ], [
            'model.required' => '适用模型必填！',
            'model.unique' => '适用模型唯一！',
            'name.required' => '名称必填！',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }
        $instance = Workflow::query()->findOrFail($id);
        if (!$instance->save()) {
            return response()->json([
                'message' => '资源更新失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源更新成功！'
        ]);
    }

    /**
     * 资源删除
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!Workflow::destroy($id)) {
            return response()->json([
                'message' => '资源删除失败'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源删除成功！'
        ]);
    }

    /**
     * 获取模型字段
     *
     * @return JsonResponse
     */
    public function modelColumns()
    {
        $id = \request()->get('id');

        $workflow = Workflow::query()->findOrFail($id);

        $modelName = trim($workflow->getAttribute('model'));

        $nameSpace = 'App\\Models\\' . $modelName;
        $columns = constant($nameSpace . '::DYNAMIC_COLUMNS');

        return response()->json([
            'message' => '',
            'data' => $columns
        ]);
    }
}
