@php
    $items = [
        ['Dashboard','admin.dashboard','bi-speedometer2'],

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
        ['Emergency Contacts','admin.emergency-contacts.index','bi-person-lines-fill'],
        ['Referrals','admin.referrals.index','bi-gift'],
        ['Push Tokens','admin.push-tokens.index','bi-phone-vibrate'],
        ['System Health','admin.system-health','bi-activity'],
    ];
@endphp

<aside class="app-sidebar">
    <a class="brand" href="{{ route('admin.dashboard') }}">
        <span>EF</span>
        <strong>Eemo Find</strong>
    </a>

    <nav class="nav flex-column">

        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        {{-- Tracking Menu --}}
        <a class="nav-link"
           data-bs-toggle="collapse"
           href="#trackingMenu">
            <i class="bi bi-geo-alt"></i>
            <span>Tracking</span>
        </a>

        <div class="collapse {{ request()->routeIs('admin.tracking.*') ? 'show' : '' }}"
             id="trackingMenu">

            <a class="nav-link ms-3"
               href="{{ route('admin.tracking.index') }}">
                <i class="bi bi-broadcast"></i>
                Live Tracking
            </a>

            <a class="nav-link ms-3"
               href="{{ route('admin.tracking.history') }}">
                <i class="bi bi-clock-history"></i>
                Tracking History
            </a>

            <a class="nav-link ms-3"
               href="{{ route('admin.geofences.index') }}">
                <i class="bi bi-bounding-box"></i>
                Geofences
            </a>

            <a class="nav-link ms-3"
               href="{{ route('admin.sos-events.index') }}">
                <i class="bi bi-exclamation-triangle"></i>
                SOS Events
            </a>
        </div>

        @foreach($items as [$label, $route, $icon])
            @if($label!='Dashboard')
                <a class="nav-link {{ request()->routeIs($route) ? 'active' : '' }}"
                   href="{{ route($route) }}">
                    <i class="bi {{ $icon }}"></i>
                    <span>{{ $label }}</span>
                </a>
            @endif
        @endforeach

    </nav>
</aside>