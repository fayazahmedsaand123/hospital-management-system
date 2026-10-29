@extends('layouts.app')

@section('title', 'Billing Summary Report')
@section('page-title', 'Billing Summary')
@section('page-subtitle', 'Bills issued between ' . \Illuminate\Support\Carbon::parse($from)->format('d M Y') . ' and ' . \Illuminate\Support\Carbon::parse($to)->format('d M Y'))

@section('content')
    <div id="reportPage" data-report-url="{{ route('reports.billing') }}">
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
                <p class="print-tagline">Billing Summary Report</p>
                <p class="print-tagline" id="printPeriod">Period: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Bills</div>
                            <div class="stat-value" data-summary-key="total">{{ $summary['total'] }}</div>
                            <div class="stat-hint">Invoices issued</div>
                        </div>
                        <span class="stat-icon navy"><i class="bi bi-receipt"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Billed</div>
                            <div class="stat-value" data-summary-key="billed" data-money="1">Rs {{ number_format($summary['billed'], 2) }}</div>
                            <div class="stat-hint">After discounts</div>
                        </div>
                        <span class="stat-icon indigo"><i class="bi bi-file-earmark-text"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Discounts</div>
                            <div class="stat-value" data-summary-key="discount" data-money="1">Rs {{ number_format($summary['discount'], 2) }}</div>
                            <div class="stat-hint">Concessions given</div>
                        </div>
                        <span class="stat-icon amber"><i class="bi bi-percent"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Collected</div>
                            <div class="stat-value" data-summary-key="collected" data-money="1">Rs {{ number_format($summary['collected'], 2) }}</div>
                            <div class="stat-hint">Payments received</div>
                        </div>
                        <span class="stat-icon green"><i class="bi bi-cash-stack"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Outstanding</div>
                            <div class="stat-value" data-summary-key="outstanding" data-money="1">Rs {{ number_format($summary['outstanding'], 2) }}</div>
                            <div class="stat-hint">Still to collect</div>
                        </div>
                        <span class="stat-icon rose"><i class="bi bi-hourglass-split"></i></span>
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
                        <option value="">All statuses</option>
                        @foreach (\App\Models\Bill::PAYMENT_STATUSES as $status)
                            <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ $status }}</option>
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
            @include('reports.partials.billing-table', ['bills' => $bills])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reports.js') }}"></script>
@endpush
