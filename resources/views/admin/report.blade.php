@extends('layouts.admin')
@section('title', Str::headline($type) . ' Report')
@section('content')
<h1 class="h3 mb-3">{{ Str::headline($type) }} Report</h1>
<div class="panel">
    <form class="row g-3 align-items-end">
        <div class="col-md-4"><label class="form-label">From</label><input type="date" class="form-control" name="from"></div>
        <div class="col-md-4"><label class="form-label">To</label><input type="date" class="form-control" name="to"></div>
        <div class="col-md-4"><button class="btn btn-teal w-100"><i class="bi bi-file-earmark-arrow-down"></i> Generate</button></div>
    </form>
</div>
@endsection