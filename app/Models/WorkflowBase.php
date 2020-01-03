<?php

namespace App\Models;

use App\Interfaces\AuditModelInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

abstract class WorkflowBase extends Base implements AuditModelInterface
{
    /**
     * 审核操作
     */
    const ACTIONS = [
        'submit' => '提交审核',
        'pass' => '通过',
        'reject' => '驳回',
    ];

    /**
     * 状态
     */
    const STATUS = [
        '待提审',
        '流转中',
        '审核通过',
        '驳回',
    ];

    /**
     * 获取审核流程
     *
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public function workflow()
    {
        $className = last(explode('\\', static::class));
        $workflowInstance = Workflow::query()->where('model', $className)->first();

        return $workflowInstance;
    }

    /**
     * 申请-流转日志
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        $class = last(explode('\\', static::class));
        return $this->hasMany(WorkflowLog::class, 'record_id')->where('model', $class);
    }

    /**
     * 开始节点
     *
     * @return mixed
     */
    public function startNode(): WorkflowNode
    {
        $workflowInstance = $this->workflow();

        $startNode = $workflowInstance->startNode();

        return $startNode;
    }

    /**
     * 确定下级节点
     * @param $currentNode
     * @return WorkflowNode
     */
    public function nextNode(WorkflowNode $currentNode): WorkflowNode
    {
        $targetNodes = $currentNode->targetNodes()->get();
        $nextNode = null;
        $apply = $this->getAttributes();
        foreach ($targetNodes as $targetNode) {
            $result = true;
            $condition = json_decode($targetNode['pivot']['condition'], true);
            foreach ($condition as $key => $item) {
                $column = trim($item['column']);
                if (isset($apply[$column])) {
                    $logicalOperator = $key ? trim($item['logical_operator']) : '&&';
                    $relationalOperator = trim($item['relational_operator']);
                    $value = $item['value'];
                    $code = 'return ' . $result . $logicalOperator . $apply[$column] . $relationalOperator . $value . ';';
                    $result = boolval(eval($code));
                }
            }
            if ($result) {
                $nextNode = $targetNode;
                break;
            }
        }

        return $nextNode;
    }


    /**
     * 获取
     *
     * @return array
     */
    public function getProcess()
    {
        $startNode = $this->workflow()->startNode();
        $nodes = [];
        $this->formatProcess($startNode, $nodes);

        return $nodes;
    }

    /**
     * 流程
     *
     * @param WorkflowNode $node
     * @param array $nextNodes
     */
    public function formatProcess(WorkflowNode $node, array &$nextNodes)
    {
        $apply = $this->getAttributes();
        $nextNode = null;
        $nextNodes[] = $node->only(['id', 'name']);
        if (!$node->isEnd()) {
            $targetNodes = $node->targetNodes()->get();
            foreach ($targetNodes as $targetNode) {
                $result = true;
                $condition = json_decode($targetNode['pivot']['condition'], true);
                foreach ($condition as $key => $item) {
                    $column = trim($item['column']);
                    if (isset($apply[$column])) {
                        $logicalOperator = $key ? trim($item['logical_operator']) : '&&';
                        $relationalOperator = trim($item['relational_operator']);
                        $value = $item['value'];
                        $code = 'return ' . $result . $logicalOperator . $apply[$column] . $relationalOperator . $value . ';';
                        $result = boolval(eval($code));
                    }
                }
                if ($result) {
                    $nextNode = $targetNode;
                }
            }
            if ($nextNode) {
                $this->formatProcess($nextNode, $nextNodes);
            }
        }
    }

    /**
     * 日志写入
     *
     * @param $node
     * @param $action
     * @param string $remarks
     * @return Model
     */
    public function writeLog(WorkflowNode $node, string $action, $remarks = '')
    {
        $className = last(explode('\\', static::class));
        $log = $this->logs()->create([
            'record_id' => $this->getAttribute('id'),
            'model' => $className,
            'node_id' => $node->getAttribute('id'),
            'node_name' => $node->getAttribute('name'),
            'node_mode' => $node->getAttribute('mode'),
            'action' => $action,
            'remarks' => $remarks,
            'created_by' => Auth::user()->getAuthIdentifier(),
            'apply_times' => $this->getAttribute('apply_times'),
        ]);

        return $log;
    }

    /**
     * 审核通过回调
     *
     * @param array $requestData
     * @return bool|mixed
     */
    public function submitCallback($requestData = [])
    {
        $startNode = $this->startNode();
        $nextNode = $this->nextNode($startNode);

        if (!$nextNode) {
            $this->error = '无法确定下级节点！';
            return false;
        }
        try {
            // 1、更新申请
            $this->setAttribute('status', 1);
            $this->setAttribute('apply_times', $this->getAttribute('apply_times') + 1);
            $this->setAttribute('current_node', $nextNode->getAttribute('id'));
            if (!$this->save()) {
                throw new \Exception('申请更新失败，请重试！');
            }
            // 2、日志写入
            if (!$this->writeLog($startNode, 'submit', '')) {
                throw new \Exception('日志写入失败，请重试！');
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 审核操作
     *
     * @param $requestData
     * @return bool
     */
    public function audit($requestData)
    {
        $currentNode = WorkflowNode::find($this->getAttribute('current_node'));
        $nextNode = $this->nextNode($currentNode);
        $action = trim($requestData['action']);
        $remarks = trim($requestData['remarks']);
        DB::beginTransaction();
        try {
            // 日志写入
            if (!$this->writeLog($currentNode, $action, $remarks)) {
                throw new \Exception('日志写入失败，请重试！');
            }
            // 执行操作
            switch ($action) {
                case 'pass':
                    if ($nextNode->isEnd()) {
                        if (!$this->passCallback()) {
                            throw new \Exception($this->getError());
                        }
                    } else {
                        $currentId = $nextNode->getAttribute('id');
                        if (!$this->circulateCallback($currentId)) {
                            throw new \Exception($this->getError());
                        }
                    }
                    break;
                case 'reject':
                    if (!$this->rejectCallback()) {
                        throw new \Exception($this->getError());
                    }
                    break;
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 审核通过
     * 如有更新其他字段或模型，请在模型中复写此方法
     *
     * @param array $requestData
     * @return bool
     */
    public function passCallback($requestData = [])
    {
        // TODO：审核方式（普签、会签），会签时，需所有审核人通过才能通过
        // 1、更新申请
        $this->setAttribute('status', 2);
        $this->setAttribute('current_node', 0);
        if (!$this->save()) {
            $this->error = '申请更新失败！';
            return false;
        }

        return true;
    }

    /**
     * 驳回申请
     * 如有更新其他字段或模型，请在模型中复写此方法
     *
     * @param array $requestData
     * @return bool
     */
    public function rejectCallback($requestData = [])
    {
        // 1、更新申请
        $this->setAttribute('status', 3);
        $this->setAttribute('current_node', 0);
        if (!$this->save()) {
            $this->error = '申请更新失败！';
            return false;
        }

        return true;
    }


    /**
     * 申请流转
     * 如有更新其他字段或模型，请在模型中复写此方法
     *
     * @param $currentId
     * @param array $requestData
     * @return bool|mixed
     */
    public function circulateCallback($currentId, $requestData = [])
    {
        // TODO：审核方式（普签、会签），会签时，需所有审核人通过才能流转到下级节点，若有一人驳回，则驳回此申请
        $this->setAttribute('current_node', $currentId);
        if (!$this->save()) {
            $this->error = '申请更新失败！';
            return false;
        }
        return true;
    }

}
