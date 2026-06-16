<?php

namespace App\Repositories;

use App\Models\EmergencyContact;

class EmergencyContactRepository extends EloquentRepository
{
    public function __construct(EmergencyContact $model)
    {
        parent::__construct($model);
    }
}