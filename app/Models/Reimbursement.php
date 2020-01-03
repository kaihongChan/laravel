<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reimbursement extends WorkflowBase
{
    const CONDITION_COLUMNS = [
        [
            'column' => 'amount',
            'name' => '金额',
        ], [
            'column' => 'created_by',
            'name' => '创建人',
        ],
    ];

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
     * @param $requestData
     * @return bool
     */
    public function createOrUpdate($requestData)
    {

        return true;
    }


}
