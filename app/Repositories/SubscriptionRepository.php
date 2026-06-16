<?php

namespace App\Repositories;

use App\Models\Subscription;

class SubscriptionRepository extends EloquentRepository
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }
}