@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('page-subtitle', 'Operational and financial summaries with printable date ranges')

@section('content')
    <div class="row g-3">
        @foreach ($reports as $report)
            <div class="col-md-6 col-xl-4">
                <a href="{{ $report['route'] }}" class="card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="stat-icon {{ $report['tone'] }}">
                            <i class="bi {{ $report['icon'] }}"></i>
                        </span>
                        <div>
                            <h3 class="card-title mb-1">{{ $report['title'] }}</h3>
                            <p class="card-subtitle mb-2" style="line-height: 1.5;">{{ $report['description'] }}</p>
                            <span class="small fw-semibold text-teal">Open report <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @if (! count($reports))
        <div class="empty-state">
            <i class="bi bi-journal-text"></i>
            No reports are available for your role.
        </div>
    @endif
@endsection
