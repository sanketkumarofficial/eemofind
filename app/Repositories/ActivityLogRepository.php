<?php

namespace App\Repositories;

use App\Models\ActivityLog;

class ActivityLogRepository extends EloquentRepository
{
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
    }
}