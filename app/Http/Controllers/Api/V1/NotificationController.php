<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            DB::table('notifications')
                ->where('notifiable_id', $request->user()->id)
                ->latest()
                ->get()
        );
    }

    public function read(Request $request, $id)
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_id', $request->user()->id)
            ->update([
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => DB::table('notifications')
                ->where('notifiable_id', $request->user()->id)
                ->whereNull('read_at')
                ->count()
        ]);
    }
}