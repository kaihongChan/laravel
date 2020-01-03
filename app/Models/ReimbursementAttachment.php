<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReimbursementAttachment extends Model
{
    /**
     * 附件-申请
     *
     * @return BelongsTo
     */
    public function reimbursement() : BelongsTo
    {
        return $this->belongsTo(Reimbursement::class, '');
    }
}
