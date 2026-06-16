<?php

namespace App\Http\Controllers\Api;

use App\Events\DomainAction;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\EmergencyContact;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PushToken;
use App\Models\SosEvent;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\TrackingGroup;
use App\Services\FirebaseService;
use App\Services\PairingService;
use App\Services\RazorpayService;
use App\Services\SettingService;
use App\Services\SubscriptionService;
use App\Services\TicketService;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileController extends Controller
{
    public function devices(Request $request)
    {
        return $request->user()->devices()->with('snapshot')->paginate(20);
    }

    public function device(Request $request, Device $device)
    {
        $this->authorize('view', $device);

        return $device->load('snapshot');
    }

    public function heartbeat(Request $request, Device $device, TrackingService $tracking)
    {
        $this->authorize('update', $device);
        abort_unless($device->is_enabled, 403, 'Device disabled.');
        $data = $request->validate(['battery' => 'required|integer|between:0,100', 'network' => 'required|string|max:30', 'gps' => 'required|string|max:20', 'timestamp' => 'nullable|date']);

        return $tracking->heartbeat($device, $data);
    }

    public function location(Request $request, Device $device, TrackingService $tracking)
    {
        $this->authorize('update', $device);
        abort_unless($device->is_enabled && $device->user_id, 403, 'Device is disabled or unassigned.');
        $data = $request->validate(['latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180', 'speed' => 'nullable|numeric|min:0', 'accuracy' => 'nullable|numeric|min:0', 'heading' => 'nullable|numeric|between:0,360', 'battery' => 'nullable|integer|between:0,100', 'timestamp' => 'nullable|date']);

        return $tracking->location($device, $data);
    }

    public function live(Request $request, int $userId, FirebaseService $firebase)
    {
        abort_unless($this->canTrack($request->user(), $userId), 403);

        return response()->json($firebase->get("live_locations/{$userId}"));
    }

    public function history(Request $request, int $userId, string $date, FirebaseService $firebase)
    {
        abort_unless($this->canTrack($request->user(), $userId), 403);
        abort_unless((bool) strtotime($date), 422, 'Invalid date.');
        $points = array_values($firebase->get("location_history/{$userId}/{$date}") ?? []);

        return ['points' => $points, 'playback' => $this->playback($points)];
    }

    public function groups(Request $request)
    {
        return $request->user()->groups()->with(['owner', 'members.devices.snapshot', 'pairingCodes' => fn ($q) => $q->where('is_active', true)])->paginate(20);
    }

    public function createGroup(Request $request, PairingService $pairing)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string|max:2000']);
        $group = DB::transaction(function () use ($request, $data, $pairing) {
            $group = $request->user()->ownedGroups()->create($data + ['status' => 'active']);
            $group->members()->attach($request->user()->id, ['role' => 'owner', 'joined_at' => now()]);
            $pairing->refresh($group);

            return $group;
        });

        return response()->json($group->load('pairingCodes'), 201);
    }

    public function updateGroup(Request $request, TrackingGroup $group)
    {
        $this->authorize('update', $group);
        $group->update($request->validate(['name' => 'sometimes|string|max:255', 'description' => 'nullable|string|max:2000']));

        return $group;
    }

    public function deleteGroup(TrackingGroup $group)
    {
        $this->authorize('delete', $group);
        $group->delete();

        return response()->noContent();
    }

    public function redeemPairing(Request $request, PairingService $pairing)
    {
        $data = $request->validate(['code' => 'required|string']);

        return $pairing->redeem($request->user(), $data['code'])->load('members');
    }

    public function refreshPairing(TrackingGroup $group, PairingService $pairing)
    {
        $this->authorize('update', $group);

        return $pairing->refresh($group);
    }

    public function setMemberRole(Request $request, TrackingGroup $group, int $userId)
    {
        abort_unless($group->owner_id === $request->user()->id, 403);
        $role = $request->validate(['role' => 'required|in:admin,member'])['role'];
        $group->members()->updateExistingPivot($userId, ['role' => $role]);

        return $group->load('members');
    }

    public function removeMember(Request $request, TrackingGroup $group, int $userId)
    {
        $this->authorize('update', $group);
        abort_if($group->owner_id === $userId, 422, 'Owner cannot be removed.');
        $group->members()->detach($userId);

        return response()->noContent();
    }

    public function plans()
    {
        return Plan::where('is_active', true)->orderBy('price')->get();
    }

    public function subscriptions(Request $request)
    {
        return $request->user()->subscriptions()->with(['plan', 'payments'])->latest()->paginate(20);
    }

    public function purchase(Request $request, Plan $plan, SubscriptionService $subscriptions, RazorpayService $razorpay)
    {
        abort_unless($plan->is_active, 422, 'Plan disabled.');
        $subscription = $subscriptions->create($request->user(), $plan);
        $payment = $razorpay->createOrder($request->user(), $subscription);

        return response()->json(['subscription' => $subscription, 'payment' => $payment, 'razorpay_key' => app(SettingService::class)->get('razorpay_key', config('eemo.razorpay.key'))], 201);
    }

    public function verifyPayment(Request $request, Payment $payment, RazorpayService $razorpay)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        $data = $request->validate(['razorpay_payment_id' => 'required|string', 'razorpay_signature' => 'required|string']);

        return $razorpay->verify($payment, $data);
    }

    public function cancelSubscription(Request $request, Subscription $subscription, SubscriptionService $service)
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);

        return $service->cancel($subscription);
    }

    public function notifications(Request $request)
    {
        return $request->user()->notifications()->latest()->paginate(30);
    }

    public function readNotification(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $notification;
    }

    public function pushToken(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:512', 'platform' => 'required|in:android,ios,web', 'device_name' => 'nullable|string|max:255']);

        return PushToken::updateOrCreate(['token' => $data['token']], $data + ['user_id' => $request->user()->id, 'last_used_at' => now()]);
    }

    public function tickets(Request $request)
    {
        return $request->user()->tickets()->with(['replies.user', 'attachments'])->latest()->paginate(20);
    }

    public function createTicket(Request $request, TicketService $tickets)
    {
        $data = $request->validate(['category' => 'required|in:subscription,payment,tracking,device,group,account,bug,feature_request,other', 'priority' => 'required|in:low,medium,high,critical', 'subject' => 'required|string|max:255', 'description' => 'required|string|max:10000']);
        $ticket = $tickets->create($request->user(), $data);
        DomainAction::dispatch($request->user(), 'ticket.created', 'Ticket created', "Ticket {$ticket->ticket_number} was created.");

        return response()->json($ticket, 201);
    }

    public function replyTicket(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === $request->user()->id || $request->user()->can('tickets.reply'), 403);
        $data = $request->validate(['message' => 'required|string|max:10000', 'attachment' => 'nullable|file|max:10240']);
        $reply = $ticket->replies()->create(['user_id' => $request->user()->id, 'message' => $data['message'], 'is_internal' => false]);
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $ticket->attachments()->create(['reply_id' => $reply->id, 'uploaded_by' => $request->user()->id, 'original_name' => $file->getClientOriginalName(), 'path' => $file->store('tickets', 'public'), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
        }

return $reply->load('attachments');
    }

    public function triggerSos(Request $request, FirebaseService $firebase)
    {
        $data = $request->validate(['device_id' => 'nullable|exists:devices,id', 'latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180', 'message' => 'nullable|string|max:500']);
        if (! empty($data['device_id'])) {
            abort_unless($request->user()->devices()->whereKey($data['device_id'])->exists(), 403);
        }
        $uuid = (string) Str::uuid();
        $firebaseKey = $firebase->push('sos_events', ['uuid' => $uuid, 'user_id' => $request->user()->id, 'latitude' => $data['latitude'], 'longitude' => $data['longitude'], 'message' => $data['message'] ?? null, 'status' => 'active', 'timestamp' => now()->toIso8601String()]);
        $event = SosEvent::create(['uuid' => $uuid, 'user_id' => $request->user()->id, 'device_id' => $data['device_id'] ?? null, 'firebase_key' => $firebaseKey, 'status' => 'active', 'triggered_at' => now()]);
        $recipients = $request->user()->groups()->with('members')->get()->flatMap(fn ($group) => $group->members->filter(fn ($member) => in_array($member->pivot->role, ['owner', 'admin'], true)))->unique('id');
        foreach ($recipients as $recipient) {
            DomainAction::dispatch($recipient, 'sos.triggered', 'Emergency alert', "{$request->user()->name} triggered an SOS.", ['sos_id' => $event->id]);
        }

        return response()->json($event, 201);
    }

    public function contacts(Request $request)
    {
        return $request->user()->emergencyContacts;
    }

    public function addContact(Request $request)
    {
        return $request->user()->emergencyContacts()->create($request->validate(['name' => 'required|string|max:255', 'mobile' => 'required|string|max:20', 'relationship' => 'required|string|max:50', 'is_primary' => 'boolean']));
    }

    public function deleteContact(Request $request, EmergencyContact $contact)
    {
        abort_unless($contact->user_id === $request->user()->id, 403);
        $contact->delete();

        return response()->noContent();
    }

    private function canTrack($user, int $target): bool
    {
        return $user->id === $target || TrackingGroup::whereHas('members', fn ($q) => $q->where('users.id', $user->id)->whereIn('group_members.role', ['owner', 'admin']))->whereHas('members', fn ($q) => $q->where('users.id', $target))->exists();
    }

    private function playback(array $points): array
    {
        $distance = 0.0;
        $stops = [];
        $previous = null;
        $stopStart = null;
        foreach ($points as $point) {
            if ($previous) {
                $distance += $this->distance((float) $previous['latitude'], (float) $previous['longitude'], (float) $point['latitude'], (float) $point['longitude']);
            } if (($point['speed'] ?? 0) < 1) {
                $stopStart ??= $point['timestamp'] ?? null;
            } elseif ($stopStart) {
                $stops[] = ['started_at' => $stopStart, 'ended_at' => $point['timestamp'] ?? null];
                $stopStart = null;
            } $previous = $point;
        }

        return ['distance_km' => round($distance, 3), 'stops' => $stops];
    }

    private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
