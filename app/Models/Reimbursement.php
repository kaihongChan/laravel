<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Exception;

class Reimbursement extends WorkflowBase
{
    /**
     *
     */
    const CONDITION_COLUMNS = [
        [
            'column' => 'amount',
            'name' => '金额',
        ], [
            'column' => 'created_by',
            'name' => '创建人',
        ],
    ];

    /**
     *
     */
    const DYNAMIC_COLUMNS = [
        [
            'column' => '',
            'name' => '部门主管',
        ]
    ];

    /**
     * 填充字段
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type_id', 'amount', 'remarks', 'created_by', 'status', 'apply_times'
    ];

    /**
     * 隐藏字段
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 报销申请-明细清单
     *
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(ReimbursementDetail::class, 'reimbursement_id', 'id');
    }

    /**
     * 报销申请-附件
     *
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ReimbursementAttachment::class, 'reimbursement_id', 'id');
    }

    /**
     * 报销申请-报销类型
     *
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ReimbursementType::class, 'type_id', 'id');
    }

    /**
     * 申请-当前节点
     *
     * @return BelongsTo
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'current_node');
    }

    /**
     * 获取表结构
     *
     * @return array
     */
    public function getTableColumns()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    /**
     * @param array $requestData
     * @param int $id
     * @return bool
     */
    public function editOrAdd(array $requestData, $id = 0)
    {
        DB::beginTransaction();
        try {
            $details = $requestData['details'];
            $requestData['amount'] = array_sum(array_column($details, 'amount'));
            $requestData['status'] = $requestData['status'] == 1 ? 1 : 0;
            $requestData['current_node'] = 0;
            if ($id) {
                $instance = self::query()->find($id);
                if ($instance->getAttribute('status') == 1) {
                    throw new \Exception('流转中，操作不允许！');
                }
                if (!$instance->update($requestData)) {
                    throw new \Exception('报销申请更新失败！');
                }
            } else {
                $instance = self::query()->create($requestData);
                if (!$instance) {
                    throw new \Exception('报销申请创建失败！');
                }
            }

            // 直接提交审核
            if ($instance->getAttribute('status') === 1) {
                $instance->submitCallback();
            }

            // 同步关联（明细）
            if ($details && !$instance->details()->saveMany($details)) {
                throw new \Exception('同步关联失败！');
            }

            // 同步关联（附件）
            if ($requestData['attachments'] && !$instance->attachments()->saveMany($requestData['attachments'])) {
                throw new \Exception('同步关联失败！');
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            DB::rollBack();
            return false;
        }

    }


}
