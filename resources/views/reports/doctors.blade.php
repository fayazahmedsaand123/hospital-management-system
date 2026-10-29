@extends('layouts.app')

@section('title', 'Doctors & Departments Report')
@section('page-title', 'Doctors & Departments')
@section('page-subtitle', 'Workload between ' . \Illuminate\Support\Carbon::parse($from)->format('d M Y') . ' and ' . \Illuminate\Support\Carbon::parse($to)->format('d M Y'))

@section('content')
    <div id="reportPage" data-report-url="{{ route('reports.doctors') }}">
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
                <p class="print-tagline">Doctors &amp; Departments Report</p>
                <p class="print-tagline" id="printPeriod">Period: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Doctors</div>
                            <div class="stat-value" data-summary-key="total">{{ $summary['total'] }}</div>
                            <div class="stat-hint">Matching the filters</div>
                        </div>
                        <span class="stat-icon navy"><i class="bi bi-person-badge"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Available</div>
                            <div class="stat-value" data-summary-key="available">{{ $summary['available'] }}</div>
                            <div class="stat-hint">Open for appointments</div>
                        </div>
                        <span class="stat-icon teal"><i class="bi bi-check2-circle"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Appointments</div>
                            <div class="stat-value" data-summary-key="appointments">{{ $summary['appointments'] }}</div>
                            <div class="stat-hint">Visits in this period</div>
                        </div>
                        <span class="stat-icon indigo"><i class="bi bi-calendar2-week"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Completed</div>
                            <div class="stat-value" data-summary-key="completed">{{ $summary['completed'] }}</div>
                            <div class="stat-hint">Completed consultations</div>
                        </div>
                        <span class="stat-icon green"><i class="bi bi-check2-all"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <form class="filter-bar no-print" id="reportFilterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="reportSearch">Search</label>
                    <input type="search" name="q" id="reportSearch" class="form-control"
                           placeholder="Doctor name, specialization, phone" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="reportDepartment">Department</label>
                    <select name="department_id" id="reportDepartment" class="form-select">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>
                                {{ $department->department_name }}
                            </option>
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
                <div class="col-md-1">
                    <button type="button" class="btn btn-soft w-100" id="resetFilters" title="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </form>

        <div class="table-wrap" id="reportTableArea">
            @include('reports.partials.doctors-table', ['doctors' => $doctors])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reports.js') }}"></script>
@endpush
