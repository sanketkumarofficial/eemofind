@extends('layouts.admin')
@section('title', 'System Health')
@section('content')
<h1 class="h3 mb-3">System Health</h1>
<div class="row g-3">
    @foreach(['Queues','Jobs','Schedulers','Logs'] as $item)
        <div class="col-md-6 col-xl-3"><div class="metric-card"><span>{{ $item }}</span><strong>Healthy</strong></div></div>
    @endforeach
</div>
@endsection