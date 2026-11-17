<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Provisional model; duplicate policy and the full lead lifecycle remain pending. */
class Lead extends Model
{
    protected $guarded = ['*'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
