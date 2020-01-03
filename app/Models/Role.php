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
        if ($id) {
            $requestData['id'] = $id;
        }

        DB::beginTransaction();

        try {// 对象更新
            if (!self::updateOrCreate($requestData)) {
                throw new \Exception('角色保存失败！');
            }// 同步菜单关联
            if ($requestData['menus'] && !$this->menus()->sync($requestData['menus'])) {
                throw new \Exception('关联菜单同步失败！');
            }// 同步策略关联
            if ($requestData['policies'] && !$this->policies()->sync($requestData['policies'])) {
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
