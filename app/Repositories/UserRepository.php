<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends EloquentRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}