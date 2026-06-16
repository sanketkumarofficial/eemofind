<?php

namespace App\Repositories;

use App\Models\Geofence;

class GeofenceRepository extends EloquentRepository
{
    public function __construct(Geofence $model)
    {
        parent::__construct($model);
    }
}