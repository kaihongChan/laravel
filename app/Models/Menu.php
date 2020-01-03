<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Menu extends Base
{
    /**
     * 填充字段
     * @var array
     */
    protected $fillable = [
        'name', 'pid', 'i18n', 'link', 'sort', 'icon'
    ];

    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [];

    /**
     * 角色-菜单
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menus', 'menu_id', 'role_id');
    }

    /**
     * 用户-菜单
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_menus', 'menu_id', 'user_id');
    }

    /**
     * 菜单父级
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pid', 'id');
    }

    /**
     * 菜单子级
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'pid', 'id');
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
        try {
            if (!self::updateOrCreate($requestData)) {
                throw new \Exception('菜单保存失败！');
            }// 同步关联
            if ($requestData['roles'] && !$this->roles()->sync($requestData['roles'])) {
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
