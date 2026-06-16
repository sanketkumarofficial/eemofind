<?php

namespace App\Repositories;

use App\Models\Plan;

class PlanRepository extends EloquentRepository
{
    public function __construct(Plan $model)
    {
        parent::__construct($model);
    }
}