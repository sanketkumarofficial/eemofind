@extends('layouts.app')

@section('title', 'Settings')

@section('content')

<div class="container-fluid">

<div class="mb-4">
    <h2 class="fw-bold">Platform Settings</h2>
    <p class="text-muted">
        Branding, support, tracking, Firebase, payments, and theme configuration.
    </p>
</div>

<form method="POST"
      action="{{ route('admin.settings.update') }}"
      enctype="multipart/form-data">

    @csrf
    @method('PUT')

    @php
        $sections = [
            'Application' => [
                ['app_name', 'App Name', 'text'],
                ['logo', 'Logo', 'file'],
                ['favicon', 'Favicon', 'file'],
                ['theme_default', 'Default Theme', 'select'],
            ],

            'Support' => [
                ['support_email', 'Support Email', 'email'],
                ['support_mobile', 'Support Mobile', 'text'],
                ['whatsapp_number', 'WhatsApp Number', 'text'],
            ],

            'Tracking & Pairing' => [
                ['heartbeat_interval', 'Heartbeat Interval (Minutes)', 'number'],
                ['offline_timeout', 'Offline Timeout (Minutes)', 'number'],
                ['pairing_code_length', 'Pairing Code Length', 'number'],
            ],

            'Firebase' => [
                ['firebase_project_id', 'Project ID', 'text'],
                ['firebase_database_url', 'Database URL', 'url'],
                ['firebase_service_account', 'Service Account JSON', 'file'],
            ],

            'Razorpay' => [
                ['razorpay_key', 'Key ID', 'text'],
                ['razorpay_secret', 'Secret Key', 'password'],
            ],
        ];
    @endphp

    <div class="row g-4">

        @foreach($sections as $section => $fields)

            <div class="col-lg-6">

                <div class="card shadow-sm h-100">

                    <div class="card-header">
                        <h5 class="mb-0">{{ $section }}</h5>
                    </div>

                    <div class="card-body">

                        @foreach($fields as $field)

                            @php
                                [$name, $label, $type] = $field;
                            @endphp

                            <div class="mb-3">

                                <label class="form-label">
                                    {{ $label }}
                                </label>

                                @if($type === 'select')

                                    <select name="{{ $name }}" class="form-select">

                                        <option value="light"
                                            @selected(($values[$name] ?? '') === 'light')>
                                            Light
                                        </option>

                                        <option value="dark"
                                            @selected(($values[$name] ?? '') === 'dark')>
                                            Dark
                                        </option>

                                    </select>

                                @else

                                    <input
                                        type="{{ $type }}"
                                        name="{{ $name }}"
                                        class="form-control"
                                        value="{{ in_array($type, ['file', 'password']) ? '' : ($values[$name] ?? '') }}"
                                        @if(in_array($name, [
                                            'app_name',
                                            'heartbeat_interval',
                                            'offline_timeout',
                                            'pairing_code_length'
                                        ]))
                                            required
                                        @endif
                                    >

                                @endif

                            </div>

                        @endforeach

                    </div>

                </div>

            </div>

        @endforeach

    </div>

    <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary px-5">
            Save Settings
        </button>
    </div>

</form>

</div>

@endsection
