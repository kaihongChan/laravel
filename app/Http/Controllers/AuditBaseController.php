<?php


namespace App\Http\Controllers;


use App\Models\WorkflowBase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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
        $searParams = request()->all();
        $pageIndex = isset($searParams['pi']) ? intval($searParams['pi']) : 1;
        $pageSize = isset($searParams['ps']) ? intval($searParams['ps']) : 10;

        $user = auth()->user();
        $roleNodes = DB::table('user_roles')
            ->join('workflow_node_roles', 'user_roles.role_id', '=',
                'workflow_node_roles.role_id', 'right')
            ->where('user_roles.user_id', $user->getAuthIdentifier())
            ->distinct()->get('node_id');

        // TODO：部门节点
        $userNodes = $user->nodes()->get(['node_id']);

        $roleNodes = array_column($roleNodes->toArray(), 'node_id');
        $userNodes = array_column($userNodes->toArray(), 'node_id');
        $auditNodes = array_unique(array_merge($roleNodes, $userNodes));

        if (empty($auditNodes)) {
            return response()->json([
                'message' => '资源列表获取成功！',
                'data' => []
            ]);
        }

        $list = $this->modelClass::query()->whereIn('current_node', $auditNodes)
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