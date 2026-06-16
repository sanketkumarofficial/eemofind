<?php

namespace App\Repositories;

use App\Models\SupportTicket;

class SupportTicketRepository extends EloquentRepository
{
    public function __construct(SupportTicket $model)
    {
        parent::__construct($model);
    }
}