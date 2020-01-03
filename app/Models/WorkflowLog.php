<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowLog extends Model
{
    /**
     *
     */
    const UPDATED_AT = null;

    /**
     * 填充字段
     * @var array
     */
    protected $fillable = [
        'record_id', 'model', 'node_id', 'node_name', 'node_mode', 'action', 'remarks', 'created_by','apply_times'
    ];

    /**
     * 创建人
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
