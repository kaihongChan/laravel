<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReimbursementDetail extends Model
{
    protected $fillable = [
        'id', 'reimbursement_id', 'project_id', 'date', 'remarks', 'amount'
    ];
}
