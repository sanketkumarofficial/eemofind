<?php

namespace App\Models;

class AdminNotification extends BaseModel
{
    protected $table = 'admin_notifications';
    public function user() { return $this->belongsTo(User::class); }
}