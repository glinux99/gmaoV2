<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUser extends Pivot
{
    /**
     * Indique si les IDs sont auto-incrémentés.
     *
     * @var bool
     */
    public $incrementing = true;

}
