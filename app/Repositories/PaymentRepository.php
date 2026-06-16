<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository extends EloquentRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }
}