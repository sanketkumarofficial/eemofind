@extends('layouts.admin')
@section('title', 'Dashboard - Eemo Find')
@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Live operating view for users, devices, revenue, and support.</p>
    </div>
    <a class="btn btn-teal" href="{{ route('admin.reports.show', 'users') }}"><i class="bi bi-download"></i> Export Reports</a>
</div>
<div class="row g-3">
    @foreach($stats as $label => $value)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card">
                <span>{{ $label }}</span>
                <strong>{{ is_numeric($value) ? number_format($value) : $value }}</strong>
            </div>
        </div>
    @endforeach
</div>
<div class="row g-3 mt-1">
    @foreach(['Revenue Trend','Subscription Growth','User Growth','Device Analytics','Online vs Offline','Ticket Analytics'] as $chart)
        <div class="col-12 col-xl-6">
            <div class="panel">
                <div class="panel-title">{{ $chart }}</div>
                <div id="{{ Str::slug($chart) }}" class="chart"></div>
            </div>
        </div>
    @endforeach
</div>
@push('scripts')
<script>
document.querySelectorAll('.chart').forEach((el) => {
    new ApexCharts(el, {
        chart: {type: 'area', height: 220, toolbar: {show: false}},
        colors: ['#00897b'],
        series: [{name: el.id, data: [12, 19, 15, 28, 32, 44, 39]}],
        xaxis: {categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul']},
        stroke: {curve: 'smooth'},
    }).render();
});
</script>
@endpush
@endsection