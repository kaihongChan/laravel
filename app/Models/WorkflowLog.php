<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'record_id', 'model', 'node_id', 'node_name', 'node_mode', 'action', 'remarks', 'created_by','apply_times'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
