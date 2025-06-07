<?php

namespace App\Traits;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Resource
{
    public function resourceable()
    {
        return $this->morphTo();
    }
}
