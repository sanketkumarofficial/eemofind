<?php

namespace App\Repositories;

use App\Models\PushToken;

class PushTokenRepository extends EloquentRepository
{
    public function __construct(PushToken $model)
    {
        parent::__construct($model);
    }
}