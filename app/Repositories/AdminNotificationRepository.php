<?php

namespace App\Repositories;

use App\Models\AdminNotification;

class AdminNotificationRepository extends EloquentRepository
{
    public function __construct(AdminNotification $model)
    {
        parent::__construct($model);
    }
}