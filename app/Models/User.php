<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * 错误信息
     *
     * @var string
     */
    protected $error = '';

    /**
     * 获取错误信息
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Find the user instance for the given username.
     *
     * @param $username
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function findForPassport($username)
    {
        return $this->query()->where('name', $username)
            ->orWhere('email', $username)->first();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'mobile', 'email', 'password', 'status', 'nickname'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * 用户-角色
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles',
            'user_id', 'role_id');
    }

    /**
     * 用户-策略
     *
     * @return BelongsToMany
     */
    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(Policy::class, 'user_policies',
            'user_id', 'policy_id');
    }

    /**
     * 用户-菜单
     *
     * @return BelongsToMany
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'user_menus',
            'user_id', 'menu_id');
    }

    /**
     * 用户-部门
     *
     * @return BelongsToMany
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_users',
            'user_id', 'dept_id')->withPivot('is_manager');
    }

    /**
     * 审核人-审核节点
     *
     * @return BelongsToMany
     */
    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(WorkflowNode::class, 'workflow_node_users',
            'user_id', 'node_id');
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
            // 数据保存
            $instance = self::query()->updateOrCreate($requestData);
            if (!$instance) {
                throw new \Exception('用户保存失败！');
            }
            // 同步关联
            if ($requestData['roles'] && !$instance->roles()->sync($requestData['roles'])) {
                throw new \Exception('同步关联失败！');
            };
            // 同步关联
            if ($requestData['departments'] && !$instance->departments()->sync($requestData['departments'])) {
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
