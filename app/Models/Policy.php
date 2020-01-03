<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Policy extends Base
{
    /**
     * 填充字段
     * @var array
     */
    protected $fillable = [
        'identify', 'name', 'description'
    ];

    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [];

    /**
     * 角色-策略
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_policies', 'policy_id', 'role_id');
    }

    /**
     * 策略-权限
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'policy_permissions', 'policy_id', 'permission_id');
    }

    /**
     * 用户-策略
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_policies', 'policy_id', 'user_id');
    }

    /**
     * 创建或者更新
     *
     * @param array $requestData
     * @param int $id
     * @return bool
     */
    public function editOrAdd(array $requestData, $id = 0)
    {
        DB::beginTransaction();
        try {
            // 资源保存
            if ($id) {
                $instance = self::query()->find($id);
                if (!$instance->update($requestData)) {
                    throw new \Exception('策略更新失败！');
                }
            } else {
                $instance = self::query()->create($requestData);
                if (!$instance) {
                    throw new \Exception('策略创建失败！');
                }
            }

            // 同步关联（策略-权限）
            if (!$instance->permissions()->sync($requestData['permissions'])) {
                throw new \Exception('同步关联失败！');
            };
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error = $e->getMessage();
            return false;
        }
    }
}
