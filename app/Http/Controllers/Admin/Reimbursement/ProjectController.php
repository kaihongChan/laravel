<?php

namespace App\Http\Controllers\Admin\Reimbursement;

use App\Http\Controllers\Controller;
use App\Models\ReimbursementProject;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $searParams = request()->all();
        $pageIndex = isset($searParams['pi']) ? intval($searParams['pi']) : 1;
        $pageSize = isset($searParams['ps']) ? intval($searParams['ps']) : 10;

        $query = ReimbursementProject::query();
        isset($searParams['keywords']) &&
        $query->where('name', 'like', '%' . trim($searParams['keywords']) . '%');

        $list = $query->paginate($pageSize, '*', 'pi', $pageIndex);

        return response()->json([
            'message' => '资源列表获取成功！',
            'data' => $list
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'name' => 'required|string|unique:reimbursement_types',
        ], [
            'name.required' => '名称必填！',
            'name.unique' => '名称唯一！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = new ReimbursementProject($requestData);
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $resource = ReimbursementProject::query()->findOrFail($id);

        return response()->json([
            'data' => $resource,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $requestData = $request->except('id');
        $validator = validator($requestData, [
            'name' => 'required|string|unique:reimbursement_types,id,' . $id,
        ], [
            'name.required' => '名称必填！',
            'name.unique' => '名称唯一！',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }
        if (!ReimbursementProject::query()->findOrFail($id)->get()->update($requestData)) {
            return response()->json([
                'message' => '资源更新失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => '资源更新成功！']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!ReimbursementProject::destroy($id)) {
            return response()->json([
                'message' => '资源删除失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => '资源删除成功！']);
    }

    /**
     * 全部
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $list = ReimbursementProject::all(['id', 'name']);

        return response()->json([
            'message' => '资源列表获取成功！',
            'data' => $list
        ]);
    }
}
