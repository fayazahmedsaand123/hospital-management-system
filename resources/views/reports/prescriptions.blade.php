@extends('layouts.app')

@section('title', 'Prescriptions Report')
@section('page-title', 'Prescriptions Report')
@section('page-subtitle', ($isDoctor ? 'Your prescriptions between ' : 'Prescriptions between ')
    . \Illuminate\Support\Carbon::parse($from)->format('d M Y') . ' and ' . \Illuminate\Support\Carbon::parse($to)->format('d M Y'))

@section('content')
    <div id="reportPage" data-report-url="{{ route('reports.prescriptions') }}">
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
                <p class="print-tagline">Prescriptions Report</p>
                <p class="print-tagline" id="printPeriod">Period: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Prescriptions</div>
                            <div class="stat-value" data-summary-key="total">{{ $summary['total'] }}</div>
                            <div class="stat-hint">Written in this period</div>
                        </div>
                        <span class="stat-icon green"><i class="bi bi-file-medical"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Patients</div>
                            <div class="stat-value" data-summary-key="patients">{{ $summary['patients'] }}</div>
                            <div class="stat-hint">Unique patients treated</div>
                        </div>
                        <span class="stat-icon teal"><i class="bi bi-people"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Medicines</div>
                            <div class="stat-value" data-summary-key="medicines">{{ $summary['medicines'] }}</div>
                            <div class="stat-hint">Medicine lines prescribed</div>
                        </div>
                        <span class="stat-icon navy"><i class="bi bi-capsule"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <form class="filter-bar no-print" id="reportFilterForm">
            <div class="row g-2 align-items-end">
                <div class="{{ $isDoctor ? 'col-md-6' : 'col-md-4' }}">
                    <label class="form-label" for="reportSearch">Search</label>
                    <input type="search" name="q" id="reportSearch" class="form-control"
                           placeholder="Patient name, code or diagnosis" value="{{ request('q') }}">
                </div>
                @unless ($isDoctor)
                    <div class="col-md-2">
                        <label class="form-label" for="reportDoctor">Doctor</label>
                        <select name="doctor_id" id="reportDoctor" class="form-select">
                            <option value="">All doctors</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected((string) request('doctor_id') === (string) $doctor->id)>
                                    {{ $doctor->doctor_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endunless
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
            @include('reports.partials.prescriptions-table', ['prescriptions' => $prescriptions])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reports.js') }}"></script>
@endpush
