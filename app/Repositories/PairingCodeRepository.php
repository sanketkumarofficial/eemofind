<?php

namespace App\Repositories;

use App\Models\PairingCode;

class PairingCodeRepository extends EloquentRepository
{
    public function __construct(PairingCode $model)
    {
        parent::__construct($model);
    }
}