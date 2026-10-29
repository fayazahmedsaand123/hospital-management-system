@extends('layouts.app')

@section('title', 'Payments Received Report')
@section('page-title', 'Payments Received')
@section('page-subtitle', 'Bills with payments between ' . \Illuminate\Support\Carbon::parse($from)->format('d M Y') . ' and ' . \Illuminate\Support\Carbon::parse($to)->format('d M Y'))

@section('content')
    <div id="reportPage" data-report-url="{{ route('reports.payments') }}">
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
                <p class="print-tagline">Payments Received Report</p>
                <p class="print-tagline" id="printPeriod">Period: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Bills Paid</div>
                            <div class="stat-value" data-summary-key="total">{{ $summary['total'] }}</div>
                            <div class="stat-hint">With at least one payment</div>
                        </div>
                        <span class="stat-icon navy"><i class="bi bi-receipt"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Collected</div>
                            <div class="stat-value" data-summary-key="collected" data-money="1">Rs {{ number_format($summary['collected'], 2) }}</div>
                            <div class="stat-hint">Total payments received</div>
                        </div>
                        <span class="stat-icon green"><i class="bi bi-cash-stack"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Average</div>
                            <div class="stat-value" data-summary-key="average" data-money="1">Rs {{ number_format($summary['average'], 2) }}</div>
                            <div class="stat-hint">Per bill</div>
                        </div>
                        <span class="stat-icon indigo"><i class="bi bi-graph-up"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Fully Paid</div>
                            <div class="stat-value" data-summary-key="paid">{{ $summary['paid'] }}</div>
                            <div class="stat-hint">Settled bills</div>
                        </div>
                        <span class="stat-icon teal"><i class="bi bi-check2-all"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Partial</div>
                            <div class="stat-value" data-summary-key="partial">{{ $summary['partial'] }}</div>
                            <div class="stat-hint">Still have a balance</div>
                        </div>
                        <span class="stat-icon amber"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <form class="filter-bar no-print" id="reportFilterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="reportSearch">Search</label>
                    <input type="search" name="q" id="reportSearch" class="form-control"
                           placeholder="Bill number, patient name or code" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="reportStatus">Payment status</label>
                    <select name="payment_status" id="reportStatus" class="form-select">
                        <option value="">All paid bills</option>
                        <option value="Paid" @selected(request('payment_status') === 'Paid')>Paid</option>
                        <option value="Partially Paid" @selected(request('payment_status') === 'Partially Paid')>Partially Paid</option>
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
            @include('reports.partials.payments-table', ['bills' => $bills])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reports.js') }}"></script>
@endpush
