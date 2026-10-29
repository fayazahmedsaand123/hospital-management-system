@extends('layouts.app')

@section('title', 'Outstanding Balances Report')
@section('page-title', 'Outstanding Balances')
@section('page-subtitle', 'Unpaid balances for bills issued between ' . \Illuminate\Support\Carbon::parse($from)->format('d M Y') . ' and ' . \Illuminate\Support\Carbon::parse($to)->format('d M Y'))

@section('content')
    <div id="reportPage" data-report-url="{{ route('reports.outstanding') }}">
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
                <p class="print-tagline">Outstanding Balances Report</p>
                <p class="print-tagline" id="printPeriod">Period: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Bills Owed</div>
                            <div class="stat-value" data-summary-key="total">{{ $summary['total'] }}</div>
                            <div class="stat-hint">With a balance</div>
                        </div>
                        <span class="stat-icon rose"><i class="bi bi-exclamation-diamond"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Outstanding</div>
                            <div class="stat-value" data-summary-key="outstanding" data-money="1">Rs {{ number_format($summary['outstanding'], 2) }}</div>
                            <div class="stat-hint">Total still owed</div>
                        </div>
                        <span class="stat-icon amber"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Unpaid</div>
                            <div class="stat-value" data-summary-key="unpaid">{{ $summary['unpaid'] }}</div>
                            <div class="stat-hint">No payment at all</div>
                        </div>
                        <span class="stat-icon navy"><i class="bi bi-receipt"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Partial</div>
                            <div class="stat-value" data-summary-key="partial">{{ $summary['partial'] }}</div>
                            <div class="stat-hint">Partially settled</div>
                        </div>
                        <span class="stat-icon indigo"><i class="bi bi-pie-chart"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Oldest Bill</div>
                            <div class="stat-value" style="font-size: 1.15rem;" data-summary-key="oldest">{{ $summary['oldest'] }}</div>
                            <div class="stat-hint">Longest pending balance</div>
                        </div>
                        <span class="stat-icon green"><i class="bi bi-clock-history"></i></span>
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
                        <option value="">Unpaid &amp; partial</option>
                        <option value="Unpaid" @selected(request('payment_status') === 'Unpaid')>Unpaid</option>
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
            @include('reports.partials.outstanding-table', ['bills' => $bills])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reports.js') }}"></script>
@endpush
