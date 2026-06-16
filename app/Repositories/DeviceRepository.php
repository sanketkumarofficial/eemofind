<?php

namespace App\Repositories;

use App\Models\Device;

class DeviceRepository extends EloquentRepository
{
    public function __construct(Device $model)
    {
        parent::__construct($model);
    }
}