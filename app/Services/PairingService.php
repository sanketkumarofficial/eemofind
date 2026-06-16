<?php

namespace App\Services;

use App\Models\PairingCode;
use App\Models\TrackingGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PairingService
{
    public function refresh(TrackingGroup $group): PairingCode
    {
        return DB::transaction(function () use ($group) {
            $group->pairingCodes()->where('is_active', true)->update(['is_active' => false]);
            do {
                $code = Str::upper(Str::random((int) app(SettingService::class)->get('pairing_code_length', config('eemo.pairing_code_length'))));
            } while (PairingCode::where('code', $code)->exists());

            return $group->pairingCodes()->create(['code' => $code, 'is_active' => true]);
        });
    }

    public function redeem(User $user, string $code): TrackingGroup
    {
        return DB::transaction(function () use ($user, $code) {
            $pairing = PairingCode::where('code', Str::upper($code))->lockForUpdate()->first();
            if (! $pairing || ! $pairing->is_active || $pairing->used_at || ($pairing->expires_at && $pairing->expires_at->isPast())) {
                throw ValidationException::withMessages(['code' => 'This pairing code is invalid or has expired.']);
            }
            $pairing->group->members()->syncWithoutDetaching([$user->id => ['role' => 'member', 'joined_at' => now()]]);
            $pairing->update(['used_by' => $user->id, 'used_at' => now(), 'is_active' => false]);
            $this->refresh($pairing->group);

            return $pairing->group;
        });
    }
}
