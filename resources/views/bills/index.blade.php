@extends('layouts.app')

@section('title', 'Billing')
@section('page-title', 'Billing & Payments')
@section('page-subtitle', 'Create bills, record payments and track outstanding balances')

@section('content')
    <div id="billsPage"
         data-index-url="{{ route('bills.index') }}"
         data-appointments-url="{{ route('lookups.appointments') }}"
         data-can-delete="{{ auth()->user()->isAdmin() ? '1' : '0' }}">

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Bills</div>
                            <div class="stat-value" id="summaryTotal">{{ $summary['total'] }}</div>
                            <div class="stat-hint">Total bills issued</div>
                        </div>
                        <span class="stat-icon navy"><i class="bi bi-receipt"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Unpaid</div>
                            <div class="stat-value" id="summaryUnpaid">{{ $summary['unpaid'] }}</div>
                            <div class="stat-hint">Bills with no payment</div>
                        </div>
                        <span class="stat-icon rose"><i class="bi bi-exclamation-circle"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Outstanding</div>
                            <div class="stat-value" id="summaryOutstanding">Rs {{ number_format($summary['outstanding'], 0) }}</div>
                            <div class="stat-hint">Balance still to collect</div>
                        </div>
                        <span class="stat-icon amber"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Collected</div>
                            <div class="stat-value" id="summaryCollected">Rs {{ number_format($summary['collected'], 0) }}</div>
                            <div class="stat-hint">Payments received</div>
                        </div>
                        <span class="stat-icon green"><i class="bi bi-cash-stack"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="text-muted small">Total: <span class="fw-semibold" id="totalCount">{{ $bills->total() }}</span> matching bills</div>
            <button type="button" class="btn btn-primary" id="addBillBtn">
                <i class="bi bi-plus-circle-fill me-1"></i> New Bill
            </button>
        </div>

        <form class="filter-bar" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="billSearch">Search</label>
                    <input type="search" name="q" id="billSearch" class="form-control"
                           placeholder="Bill number, patient name or code" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filterPaymentStatus">Payment status</label>
                    <select name="payment_status" id="filterPaymentStatus" class="form-select">
                        <option value="">All statuses</option>
                        @foreach ($paymentStatuses as $status)
                            <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="dateFrom">From</label>
                    <input type="date" name="date_from" id="dateFrom" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="dateTo">To</label>
                    <input type="date" name="date_to" id="dateTo" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-soft w-100" id="resetFilters" title="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </form>

        <div class="table-wrap" id="tableArea">
            @include('bills.partials.table', ['bills' => $bills])
        </div>
    </div>

    {{-- Create / edit bill --}}
    <div class="modal fade" id="billModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <form id="billForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="billModalTitle">New Bill</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="bill_patient">Patient</label>
                                <select name="patient_id" id="bill_patient" class="form-select" required>
                                    <option value="">Select patient</option>
                                    @foreach ($patients as $patient)
                                        <option value="{{ $patient->id }}">
                                            {{ $patient->patient_code }} — {{ $patient->patient_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="bill_appointment">
                                    Appointment <span class="text-muted">(optional)</span>
                                </label>
                                <select name="appointment_id" id="bill_appointment" class="form-select">
                                    <option value="">No linked appointment</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="bill_date">Bill date</label>
                                <input type="date" name="bill_date" id="bill_date" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="paid_amount" id="paidAmountLabel">
                                    Paid now <span class="text-muted">(optional)</span>
                                </label>
                                <input type="number" name="paid_amount" id="paid_amount" class="form-control"
                                       min="0" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0">Services &amp; Charges</h6>
                            <button type="button" class="btn btn-sm btn-soft" id="addItemBtn">
                                <i class="bi bi-plus-lg me-1"></i> Add Row
                            </button>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="min-width: 240px">Description</th>
                                        <th style="width: 110px">Qty</th>
                                        <th style="width: 140px">Unit price (Rs)</th>
                                        <th style="width: 140px" class="text-end">Line total</th>
                                        <th style="width: 44px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>

                        <div class="row justify-content-end mt-3">
                            <div class="col-md-5">
                                <div class="summary-line">
                                    <span>Subtotal</span>
                                    <strong id="subtotalText">Rs 0.00</strong>
                                </div>
                                <div class="summary-line align-items-center">
                                    <span>Discount (Rs)</span>
                                    <input type="number" name="discount" id="discount" class="form-control form-control-sm text-end"
                                           style="max-width: 140px;" min="0" step="0.01" value="0">
                                </div>
                                <div class="summary-line fs-6">
                                    <span class="fw-bold">Total</span>
                                    <strong id="totalText">Rs 0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="billSaveBtn">Save Bill</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Record payment --}}
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="paymentForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="detail-label">Bill</div>
                            <div class="detail-value" id="paymentBillNumber">—</div>
                            <div class="text-muted small" id="paymentPatientName">—</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <div class="detail-label">Total</div>
                                <div class="detail-value" id="paymentTotal">—</div>
                            </div>
                            <div class="col-4">
                                <div class="detail-label">Paid</div>
                                <div class="detail-value" id="paymentPaid">—</div>
                            </div>
                            <div class="col-4">
                                <div class="detail-label">Balance</div>
                                <div class="detail-value text-danger" id="paymentBalance">—</div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label" for="payment_amount">Amount received (Rs)</label>
                            <input type="number" name="amount" id="payment_amount" class="form-control"
                                   min="0.01" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-teal" id="paymentSaveBtn">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/bills.js') }}"></script>
@endpush
