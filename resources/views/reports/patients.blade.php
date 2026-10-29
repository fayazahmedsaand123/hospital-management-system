@extends('layouts.app')

@section('title', 'Patient Register Report')
@section('page-title', 'Patient Register')
@section('page-subtitle', 'Patients registered between ' . \Illuminate\Support\Carbon::parse($from)->format('d M Y') . ' and ' . \Illuminate\Support\Carbon::parse($to)->format('d M Y'))

@section('content')
    <div id="reportPage" data-report-url="{{ route('reports.patients') }}">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
            <a href="{{ route('reports.index') }}" class="btn btn-soft">
                <i class="bi bi-arrow-left me-1"></i> All reports
            </a>
            <button type="button" class="btn btn-teal" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print report
            </button>
        </div>

        <div class="d-none d-print-block mb-3">
            <div class="print-header">
                <p class="print-hospital">{{ config('app.name') }}</p>
                <p class="print-tagline">Patient Register Report</p>
                <p class="print-tagline" id="printPeriod">Period: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Registered</div>
                            <div class="stat-value" data-summary-key="total">{{ $summary['total'] }}</div>
                            <div class="stat-hint">Patients in this period</div>
                        </div>
                        <span class="stat-icon teal"><i class="bi bi-people"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Male</div>
                            <div class="stat-value" data-summary-key="male">{{ $summary['male'] }}</div>
                            <div class="stat-hint">Male patients</div>
                        </div>
                        <span class="stat-icon navy"><i class="bi bi-gender-male"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Female</div>
                            <div class="stat-value" data-summary-key="female">{{ $summary['female'] }}</div>
                            <div class="stat-hint">Female patients</div>
                        </div>
                        <span class="stat-icon rose"><i class="bi bi-gender-female"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Other</div>
                            <div class="stat-value" data-summary-key="other">{{ $summary['other'] }}</div>
                            <div class="stat-hint">Other / unspecified</div>
                        </div>
                        <span class="stat-icon indigo"><i class="bi bi-person"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <form class="filter-bar no-print" id="reportFilterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="reportSearch">Search</label>
                    <input type="search" name="q" id="reportSearch" class="form-control"
                           placeholder="Name, code, phone or email" value="{{ request('q') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="reportGender">Gender</label>
                    <select name="gender" id="reportGender" class="form-select">
                        <option value="">All genders</option>
                        @foreach (['Male', 'Female', 'Other'] as $gender)
                            <option value="{{ $gender }}" @selected(request('gender') === $gender)>{{ $gender }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="dateFrom">From</label>
                    <input type="date" name="date_from" id="dateFrom" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="dateTo">To</label>
                    <input type="date" name="date_to" id="dateTo" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-soft w-100" id="resetFilters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </form>

        <div class="table-wrap" id="reportTableArea">
            @include('reports.partials.patients-table', ['patients' => $patients])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reports.js') }}"></script>
@endpush
