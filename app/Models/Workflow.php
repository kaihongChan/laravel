<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'workflow';

    /**
     * 填充字段
     *
     * @var array
     */
    protected $fillable = [
        'name', 'name_i18n', 'model', 'description', 'status'
    ];

    /**
     * 隐藏字段
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 审核流-节点
     *
     * @return HasMany
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(WorkflowNode::class, 'workflow_id', 'id');
    }

    /**
     * 开始节点
     *
     * @return Model|HasMany|object|null
     */
    public function startNode()
    {
        return $this->nodes()->where('type', 1)->first();
    }

    /**
     * 结束节点
     *
     * @return Model|HasMany|object|null
     */
    public function endNode()
    {
        return $this->nodes()->where('type', 2)->first();
    }
}
