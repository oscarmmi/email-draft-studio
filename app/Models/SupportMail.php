<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportMail extends Model
{
    protected $fillable = [
        'subject',
        'body',
        'customer_name',
        'category',
    ];

    /** @return HasMany<Draft, $this> */
    public function drafts(): HasMany
    {
        return $this->hasMany(Draft::class);
    }
}
