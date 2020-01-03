<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReimbursementType extends Model
{
    /**
     * 填充字段
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'description'
    ];
}
