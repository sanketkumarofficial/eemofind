@extends('layouts.admin')

@section('title', 'Tracking History')

@section('content')

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Tracking History</h5>
    </div>

    <div class="card-body">
        <p>Select Device & Date to view tracking history.</p>

        <form class="row g-3">

            <div class="col-md-4">
                <label>Device</label>
                <select class="form-control">
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}">
                            {{ $device->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label>Date</label>
                <input type="date" class="form-control">
            </div>

            <div class="col-md-2">
                <label>&nbsp;</label>
                <button class="btn btn-primary w-100">
                    Search
                </button>
            </div>

        </form>
    </div>
</div>

@endsection