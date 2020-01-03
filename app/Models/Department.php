<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Department extends Model
{
    /**
     * 填充字段
     *
     * @var array
     */
    protected $fillable = [
        'pid', 'name'
    ];

    protected $hidden = [];

    /**
     * 部门-用户
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class,
            'department_users', 'user_id', 'dept_id')->withPivot('is_manager');
    }

    /**
     * 部门-负责人
     *
     * @return BelongsToMany
     */
    public function managers()
    {
        return $this->belongsToMany(User::class,
            'department_users', 'user_id', 'dept_id')->where('is_manager', 1)
            ->withPivot('is_manager');
    }

    /**
     * 部门-父级
     *
     * @return HasOne
     */
    public function parent(): HasOne
    {
        return $this->hasOne(self::class, 'pid');
    }

    /**
     * 部门-子级
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'pid');
    }

    /**
     * 部门-审核节点
     *
     * @return BelongsToMany
     */
    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(WorkflowNode::class, 'workflow_node_departments',
            'dept_id', 'node_id');
    }
}
