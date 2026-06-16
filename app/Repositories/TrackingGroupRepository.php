<?php

namespace App\Repositories;

use App\Models\TrackingGroup;

class TrackingGroupRepository extends EloquentRepository
{
    public function __construct(TrackingGroup $model)
    {
        parent::__construct($model);
    }
}