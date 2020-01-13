<?php


namespace App\Http\Controllers;


use App\Models\WorkflowBase;
use App\Models\WorkflowNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

/**
 * 审核基类
 *
 * Class AuditBaseController
 * @package App\Http\Controllers
 */
abstract class AuditBaseController extends Controller
{
    /**
     * @var WorkflowBase
     */
    protected $modelClass;

    /**
     * 提交审核
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit()
    {
        $id = request()->post('id');
        if (!$this->modelClass) {
            return response()->json([
                'message' => '模型指向不明！'
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = $this->modelClass::query()->find($id);

        if (!$instance->submitCallback()) {
            return response()->json([
                'message' => $instance->getError(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return \response()->json([
            'message' => '操作成功！',
        ]);
    }

    /**
     * 审核列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function AuditIndex()
    {
        $searchParams = request()->all();
        $pageIndex = isset($searParams['pi']) ? intval($searchParams['pi']) : 1;
        $pageSize = isset($searParams['ps']) ? intval($searchParams['ps']) : 10;

        $workflowId = $this->modelClass->workflow()->getAttribute('id');
        $userInstance = \auth()->user();
        // 当前登录用户节点
        $userNodeIds = $userInstance->nodes()->where([
            'workflow_id' => $workflowId,
            'type' => 0
        ])->allRelatedIds()->all();

        // 当前登录用户角色节点
        $userRoleIds = $userInstance->roles()->allRelatedIds()->all();
        $roleNodeIds = WorkflowNode::query()->whereHas('roles',
            function (Builder $query) use ($userRoleIds, $workflowId) {
                return $query->where('workflow_id', $workflowId)->whereIn('role_id', $userRoleIds);
            })->where('type', 0)->pluck('id')->all();

        $auditNodes = array_unique(array_merge($userNodeIds, $roleNodeIds));

        $list = $this->modelClass::query()
            ->where([
                'status' => 1,
            ])->whereIn('current_node', $auditNodes)
            ->paginate($pageSize, '*', 'pi', $pageIndex);

        return response()->json([
            'message' => '资源列表获取成功！',
            'data' => $list
        ]);

    }

    /**
     * 执行审核
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function audit()
    {
        $id = request()->post('id');
        if (!$this->modelClass) {
            return response()->json([
                'message' => '对象指向不明！'
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = $this->modelClass::query()->findOrFail($id);

        if (!$instance->audit(request()->except('id'))) {
            return response()->json([
                'message' => $instance->getError(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * 获取流程
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function process()
    {
        $id = request()->get('id');
        if (!$this->modelClass) {
            return response()->json([
                'message' => '对象指向不明！'
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = $this->modelClass::query()->findOrFail($id);

        return response()->json([
            'message' => '资源列表获取成功！',
            'data' => $instance->getProcess()
        ]);
    }

    /**
     * 审核日志
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logs()
    {
        $id = request()->get('id');
        $times = request()->get('times');
        if (!$this->modelClass) {
            return response()->json([
                'message' => '对象指向不明！'
            ], Response::HTTP_BAD_REQUEST);
        }

        $instance = $this->modelClass::query()->find($id);
        $query = $instance->logs()->with('creator');
        $times && $query->where('apply_times', $times);

        $actionArr = $this->modelClass::ACTIONS;
        $logs = $query->get();
        foreach ($logs as $key => $log) {
            $log['action_str'] = $actionArr[$log['action']];
        }
        return response()->json([
            'data' => $logs,
            'message' => '资源获取成功！'
        ]);
    }

    /**
     * 获取条件字段
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function conditionColumns()
    {
        $columns = $this->modelClass::CONDITION_COLUMNS;
        return response()->json([
            'data' => $columns,
            'message' => '资源获取成功！'
        ]);
    }

    /**
     * 获取操作符
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function conditionOperators()
    {
        $operators = config('operators');
        return response()->json([
            'data' => $operators,
            'message' => '资源获取成功！'
        ]);
    }

}