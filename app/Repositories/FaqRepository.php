<?php

namespace App\Repositories;

use App\Models\Faq;

class FaqRepository extends EloquentRepository
{
    public function __construct(Faq $model)
    {
        parent::__construct($model);
    }
}