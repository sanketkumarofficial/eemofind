<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function create(User $user, array $data): SupportTicket
    {
        return DB::transaction(function () use ($user, $data) {
            $date = now()->format('Ymd');
            $last = SupportTicket::where('ticket_number', 'like', "EMO-{$date}-%")->lockForUpdate()->orderByDesc('id')->value('ticket_number');
            $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

            return SupportTicket::create(array_merge($data, ['user_id' => $user->id, 'ticket_number' => sprintf('EMO-%s-%04d', $date, $sequence)]));
        });
    }
}
