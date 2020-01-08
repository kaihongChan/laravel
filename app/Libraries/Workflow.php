<?php


namespace App\Libraries;


use App\Models\WorkflowBase;
use App\Models\WorkflowNode;
use Illuminate\Database\Eloquent\Model;

class Workflow
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * Workflow constructor.
     * @param WorkflowBase $model
     */
    public function __construct(WorkflowBase $model)
    {
        $this->model = $model;
    }

    /**
     * 获取审核流程
     *
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public function workflow()
    {
        $className = last(explode('\\', get_class($this->model)));
        $workflowInstance = \App\Models\Workflow::query()->where('model', $className)->first();

        return $workflowInstance;
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
        $apply = $this->model->getAttributes();
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
        $apply = $this->model->getAttributes();
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
}