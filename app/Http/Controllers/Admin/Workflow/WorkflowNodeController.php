<?php

namespace App\Http\Controllers\Admin\Workflow;

use App\Http\Controllers\Controller;
use App\Models\WorkflowNode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkflowNodeController extends Controller
{

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
            'workflow_id' => 'required',
            'name' => 'required',
            'type' => 'required',
            'mode' => 'required',
        ], [
            'workflow_id.required' => '所属审核流程必填！',
            'name.required' => '名称必填！',
            'type.required' => '节点类型必填！',
            'mode.required' => '审核类型必填！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        // 开始及结束节点只能有一个
        if ($requestData['type']) {
            $startNode = WorkflowNode::query()->where('workflow_id', intval($requestData['workflow']))
                ->where('type', $requestData['type'])->get();
            if ($startNode) {
                return response()->json([
                    'message' => '流程只能有一个开始或结束节点！',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // TODO：审核人保存

        $instance = new WorkflowNode($requestData);
        if (!$instance->push()) {
            return response()->json([
                'message' => '资源创建失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        $formatData = $this->formatNode($instance->toArray());

        return response()->json([
            'message' => '资源创建成功！',
            'data' => $formatData
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
        $resource = [];

        $id && $resource = WorkflowNode::query()->findOrFail($id);

        return response()->json([
            'message' => '资源获取成功！',
            'data' => $resource
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
            'name' => 'required|unique:users,id',
        ], [
            'name.required' => '名称必填！',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        // 开始及结束节点只能有一个
        if ($requestData['type']) {
            $startNode = WorkflowNode::query()->where('workflow_id', intval($requestData['workflow']))
                ->where('type', $requestData['type'])->get();
            if ($startNode) {
                return response()->json([
                    'message' => '流程只能有一个开始或结束节点！',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // TODO：审核人更新

        $instance = WorkflowNode::query()->findOrFail($id);
        if (!$instance->update($requestData) || !$instance->push()) {
            return response()->json([
                'message' => '资源更新失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        $formatData = $this->formatNode($instance->toArray());

        return response()->json([
            'message' => '资源更新成功！',
            'data' => $formatData
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
        $resource = WorkflowNode::find($id);
        if (!$resource->sourceNodes()->detach()) {
            return response()->json([
                'message' => '分离关联失败'
            ], Response::HTTP_BAD_REQUEST);
        }
        if (!$resource->targetNodes()->detach()) {
            return response()->json([
                'message' => '分离关联失败'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$resource->destroy()) {
            return response()->json([
                'message' => '资源删除失败'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源删除成功！'
        ]);
    }

    /**
     * 格式化节点数据
     *
     * @param $node
     * @return mixed
     */
    private function formatNode(array $node)
    {
        $formatNode = [
            'id' => strval($node['id']),
            'label' => $node['name'],
            'x' => $node['position_x'],
            'y' => $node['position_y'],
        ];
        return $formatNode;
    }

    /**
     * 格式化节点列表
     *
     * @param $nodeList
     * @return mixed
     */
    private function formatNodeEdges(array $nodeList)
    {
        $edges = [];
        $nodes = [];
        foreach ($nodeList as $key => $node) {
            $nodes[] = [
                'id' => strval($node['id']),
                'label' => $node['name'],
                'x' => $node['position_x'],
                'y' => $node['position_y'],
            ];

            $targetNodes = isset($node['target_nodes']) ? $node['target_nodes'] : [];

            foreach ($targetNodes as $targetNode) {
                $source = strval($node['id']);
                $target = strval($targetNode['id']);
                $edges[] = [
                    'id' => $source . '_' . $target,
                    'source' => $source,
                    'target' => $target,
                    'label' => $targetNode['pivot']['label'],
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    /**
     * 流程数据
     *
     * @return JsonResponse
     */
    public function design()
    {
        $workflowId = intval(\request()->get('workflow_id', 0));

        $query = WorkflowNode::query();
        $workflowId && $query->with('targetNodes')->where('workflow_id', $workflowId);

        $list = $this->formatNodeEdges($query->get()->toArray());

        return response()->json([
            'message' => '资源获取成功！',
            'data' => $list
        ]);
    }

    /**
     * 获取边
     * @param int $id
     * @return JsonResponse
     */
    public function edge($id)
    {
        list($source, $target) = explode('_', $id);

        $resource = DB::table('workflow_node_edges')
            ->where('source', $source)
            ->where('target', $target)->first();

        $condition = $resource->condition ? json_decode($resource->condition, true) : [];
        $formatData = [
            'id' => $id,
            'source' => $source,
            'target' => $target,
            'condition' => $condition,
            'label' => $resource->label,
        ];
        return response()->json([
            'message' => '资源获取成功！',
            'data' => $formatData
        ]);
    }

    /**
     * 创建边
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function edgeStore(Request $request)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'source' => 'required',
            'target' => 'required',
            'conditions' => 'array'
        ], [
            'source.required' => '源节点必填！',
            'target.required' => '目标节点必填！',
            'conditions.array' => '条件数据类型错误！',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!DB::table('workflow_node_edges')->insert([
            'source' => intval($requestData['source']),
            'target' => intval($requestData['target']),
            'condition' => '',
        ])) {
            return response()->json([
                'message' => '资源创建失败！'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => '资源创建成功！'
        ]);
    }

    /**
     * 更新边
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function edgeUpdate(Request $request, $id)
    {
        $requestData = $request->all();
        $validator = validator($requestData, [
            'source' => 'required',
            'target' => 'required',
            'conditions' => 'array'
        ], [
            'source.required' => '源节点必填！',
            'target.required' => '目标节点必填！',
            'conditions.array' => '条件数据类型错误！',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_BAD_REQUEST);
        }

        list($source, $target) = explode('_', $id);

        if (!DB::table('workflow_node_edges')->where('source', intval($source))
            ->where('target', intval($target))->update([
                'condition' => json_encode($requestData['conditions'], JSON_UNESCAPED_UNICODE),
                'label' => trim($requestData['label']),
            ])) {
            return response()->json([
                'message' => '资源更新失败！'
            ], Response::HTTP_BAD_REQUEST);
        };

        return response()->json([
            'message' => '资源更新成功！'
        ]);
    }

    /**
     * 删除边
     *
     * @param $id
     * @return JsonResponse
     */
    public function edgeDestroy($id)
    {
        list($source, $target) = explode('_', $id);

        if (!DB::table('workflow_node_edges')->where('source', intval($source))
            ->where('target', intval($target))->delete()) {
            return response()->json([
                'message' => '资源删除失败！'
            ], Response::HTTP_BAD_REQUEST);
        };

        return response()->json([
            'message' => '资源删除成功！'
        ]);
    }
}
