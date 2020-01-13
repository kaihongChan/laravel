<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkflowNode extends Model
{
    /**
     * 填充字段
     *
     * @var array
     */
    protected $fillable = [
        'workflow_id', 'name', 'approval_mode', 'type', 'mode', 'position_x', 'position_y'
    ];

    /**
     * 隐藏字段
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 节点-审核流
     *
     * @return BelongsTo
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id', 'id');
    }

    /**
     * 节点-节点
     *
     * @return BelongsToMany
     */
    public function targetNodes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'workflow_node_edges',
            'source', 'target')->withPivot(['condition', 'label']);
    }

    /**
     * 节点-节点
     *
     * @return BelongsToMany
     */
    public function sourceNodes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'workflow_node_edges',
            'target', 'source')->withPivot(['condition', 'label']);
    }

    /**
     * 节点-用户
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'workflow_node_users',
            'node_id', 'user_id')->withTimestamps();
    }

    /**
     * 节点-角色
     *
     * @return BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'workflow_node_roles',
            'node_id', 'role_id')->withTimestamps();
    }

    /**
     * 判断节点类型
     *
     * @return bool
     */
    public function isEnd()
    {
        return $this->getAttribute('type') == 2;
    }
}
