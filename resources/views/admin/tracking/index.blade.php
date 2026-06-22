@extends('layouts.admin')

@section('title', 'Live Tracking')

@section('content')

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Live Device Tracking</h5>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Device</th>
                        <th>IMEI</th>
                        <th>Last Location</th>
                        <th>Last Update</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($devices as $device)
                        <tr>
                            <td>{{ $device->id }}</td>
                            <td>{{ $device->name }}</td>
                            <td>{{ $device->imei }}</td>
                            <td>
                                {{ $device->last_latitude }},
                                {{ $device->last_longitude }}
                            </td>
                            <td>{{ $device->updated_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">
                                No Devices Found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>
</div>

@endsection