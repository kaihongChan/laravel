<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Base
{
    /**
     * 填充字段
     * @var array
     */
    protected $fillable = [
        'name', 'name', 'route_name', 'description'
    ];

    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [];

    /**
     * 策略-权限
     * @return BelongsToMany
     */
    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(Policy::class, 'policy_permissions', 'permission_id', 'policy_id');
    }
}
