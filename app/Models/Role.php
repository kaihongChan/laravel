<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Role extends Base
{
    /**
     * 填充字段
     *
     * @var array
     */
    protected $fillable = [
        'identify', 'name', 'description'
    ];

    /**
     * 隐藏字段
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 角色-菜单
     *
     * @return BelongsToMany
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'role_menus',
            'role_id', 'menu_id');
    }

    /**
     * 角色-策略
     *
     * @return BelongsToMany
     */
    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(Policy::class, 'role_policies',
            'role_id', 'policy_id');
    }

    /**
     * 用户-角色
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role',
            'role_id', 'user_id');
    }

    /**
     * 角色-审核节点
     *
     * @return BelongsToMany
     */
    public function nodes()
    {
        return $this->belongsToMany(WorkflowNode::class,
            'workflow_node_roles', 'role_id', 'user_id');
    }

    /**
     * 数据保存
     *
     * @param array $requestData
     * @param int $id
     * @return bool
     */
    public function editOrAdd(array $requestData, $id = 0)
    {
        DB::beginTransaction();
        try {
            // 对象更新
            if ($id) {
                $instance = self::query()->find($id);
                if (!$instance->update($requestData)) {
                    throw new \Exception('角色更新失败！');
                }
            } else {
                $instance = self::query()->create($requestData);
                if (!$instance) {
                    throw new \Exception('角色创建失败！');
                }
            }

            // 同步关联（菜单）
            if ($requestData['menus'] && !$instance->menus()->sync($requestData['menus'])) {
                throw new \Exception('关联菜单同步失败！');
            }
            // 同步关联（策略）
            if ($requestData['policies'] && !$instance->policies()->sync($requestData['policies'])) {
                throw new \Exception('关联策略同步失败！');
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
