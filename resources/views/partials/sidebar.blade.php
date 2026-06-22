@php
    $items = [
        ['Dashboard','dashboard','bi-speedometer2'],

        ['Tracking','admin.tracking.index','bi-geo-alt'],

        ['Users','admin.users.index','bi-people'],
        ['Devices','admin.devices.index','bi-router'],
        ['Groups','admin.tracking-groups.index','bi-diagram-3'],
        ['Pairing Codes','admin.pairing-codes.index','bi-qr-code'],
        ['Plans','admin.plans.index','bi-box-seam'],
        ['Subscriptions','admin.subscriptions.index','bi-arrow-repeat'],
        ['Payments','admin.payments.index','bi-credit-card'],
        ['Support Tickets','admin.support-tickets.index','bi-life-preserver'],
        ['FAQs','admin.faqs.index','bi-question-circle'],
        ['Notifications','admin.notifications.index','bi-bell'],
        ['Activity Logs','admin.activity-logs.index','bi-clock-history'],
        ['SOS Events','admin.sos-events.index','bi-exclamation-triangle'],
        ['Geofences','admin.geofences.index','bi-bounding-box'],
        ['Emergency Contacts','admin.emergency-contacts.index','bi-person-lines-fill'],
        ['Referrals','admin.referrals.index','bi-gift'],
        ['Push Tokens','admin.push-tokens.index','bi-phone-vibrate'],
        ['System Health','admin.system-health','bi-activity'],
    ];
@endphp

<aside class="app-sidebar">
    <a class="brand" href="{{ route('dashboard') }}">
        <span>EF</span>
        <strong>Eemo Find</strong>
    </a>

    <nav class="nav flex-column">
        @foreach($items as [$label, $route, $icon])
            <a class="nav-link {{ request()->routeIs($route) ? 'active' : '' }}"
               href="{{ route($route) }}">
                <i class="bi {{ $icon }}"></i>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
</aside>