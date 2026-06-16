<?php

namespace App\Repositories;

use App\Models\NotificationLog;

class NotificationLogRepository extends EloquentRepository
{
    public function __construct(NotificationLog $model)
    {
        parent::__construct($model);
    }
}