<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Draft extends Model
{
    protected $fillable = [
        'support_mail_id',
        'tone',
        'text',
        'status',
    ];

    /** @return BelongsTo<SupportMail, $this> */
    public function supportMail(): BelongsTo
    {
        return $this->belongsTo(SupportMail::class);
    }
}
