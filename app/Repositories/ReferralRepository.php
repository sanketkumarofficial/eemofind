<?php

namespace App\Repositories;

use App\Models\Referral;

class ReferralRepository extends EloquentRepository
{
    public function __construct(Referral $model)
    {
        parent::__construct($model);
    }
}