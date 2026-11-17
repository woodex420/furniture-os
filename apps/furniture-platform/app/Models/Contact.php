<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Provisional read model; there are no customer write endpoints yet. */
class Contact extends Model
{
    protected $guarded = ['*'];
}
