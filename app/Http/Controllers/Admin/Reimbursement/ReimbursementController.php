<?php

namespace App\Http\Controllers\Admin\Reimbursement;

use App\Http\Controllers\AuditBaseController;
use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReimbursementController extends AuditBaseController
{
    /**
     * @var Reimbursement
     */
    protected $modelClass;

    /**
     * ReimbursementController constructor.
     *
     * @param Reimbursement $reimbursement
     */
    public function __construct(Reimbursement $reimbursement)
    {
        $this->modelClass = $reimbursement;
    }

    /**
     * 资源列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $searParams = request()->all();
        $pageIndex = isset($searParams['pi']) ? intval($searParams['pi']) : 1;
        $pageSize = isset($searParams['ps']) ? intval($searParams['ps']) : 10;

        $query = $this->modelClass::query();
        isset($searParams['keywords']) &&
        $query->where('name', 'like', '%' . trim($searParams['keywords']) . '%');

        $list = $query->paginate($pageSize, '*', 'pi', $pageIndex);

        return response()->json([
            'message' => '资源列表获取成功！',
            'data' => $list
        ]);
    }

    /**
     * 创建
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'name' => 'required|string',
            'details' => 'required|array',
            'attachments' => 'array',
        ], [
            'name.required' => '名称必填！',
            'details.require' => '明细必填！',
            'details.array' => '明细格式错误！',
            'attachments.array' => '附件格式错误！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();
        try {
            $requestData['created_by'] = auth()->user()->getAuthIdentifier();
            $instance = new Reimbursement($requestData);
            $details = $requestData['details'];
            $instance->setAttribute('amount', array_sum(array_column($details, 'amount')));
            // 直接提交审核
            if ($requestData['status'] === 1) {
                $instance->submitCallback();
            } else {
                $instance->setAttribute('current_node', 0);
                $instance->setAttribute('status', 0);
                if (!$instance->save()) {
                    throw new \Exception('资源创建失败！');
                }
            }

            // 保存明细
            if (!$instance->details()->saveMany($details)) {
                throw new \Exception('关联数据创建失败！');
            }

            // 保存附件
            if ($requestData['attachments'] && !$instance->attachments()->saveMany($requestData['attachments'])) {
                throw new \Exception('关联数据创建失败！');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源创建成功！'
        ], Response::HTTP_CREATED);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $resource = Reimbursement::with(['details', 'attachments'])->findOrFail($id);

        return response()->json([
            'data' => $resource,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $requestData = $request->except('id');
        $validator = validator($requestData, [
            'name' => 'required|string',
        ], [
            'name.required' => '名称必填！',
            'name.unique' => '名称唯一！',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = Reimbursement::query()->findOrFail($id);
        if ($instance->getAttribute('status') == 1) {
            return response()->json([
                'message' => '流转状态操作不允许！'
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();
        try {
            // 更新明细
            foreach ($requestData['details'] as $detail) {
                $detailId = intval($detail['id']);
                if ($detailId) {
                    if (!$instance->details()->update($detail)) {
                        throw new \Exception('明细更新失败！');
                    } else {
                        if (!$instance->details()->create($detail)) {
                            throw new \Exception('明细创建失败！');
                        }
                    }
                }
            }
            // 更新附件
            foreach ($request['attachments'] as $attachment) {
                $attachmentId = intval($attachment['id']);
                if ($attachmentId) {
                    if (!$instance->attachments()->update($attachment)) {
                        throw new \Exception('附件更新失败！');
                    }
                } else {
                    if (!$instance->attachments()->create($attachment)) {
                        throw new \Exception('附件创建失败！');
                    }
                }

            }
            // 更新申请
            if (!$instance->update($requestData)) {
                throw new \Exception('资源更新失败！');
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => '资源更新成功！']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Reimbursement::destroy($id)) {
            return response()->json([
                'message' => '资源删除失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => '资源删除成功！']);
    }
}
