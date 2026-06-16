<?php

namespace App\Repositories;

use App\Models\SosEvent;

class SosEventRepository extends EloquentRepository
{
    public function __construct(SosEvent $model)
    {
        parent::__construct($model);
    }
}