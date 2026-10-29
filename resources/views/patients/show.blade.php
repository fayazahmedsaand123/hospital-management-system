@extends('layouts.app')

@section('title', $patient->patient_name)
@section('page-title', 'Patient Profile')
@section('page-subtitle', $patient->patient_code . ' · ' . $patient->patient_name)

@section('content')
    @php($user = auth()->user())

    <div class="d-flex justify-content-between align-items-center mb-3">
        @if ($user->isAdminOrReceptionist())
            <a href="{{ route('patients.index') }}" class="btn btn-soft">
                <i class="bi bi-arrow-left me-1"></i> Back to patients
            </a>
        @else
            <a href="{{ route('appointments.index') }}" class="btn btn-soft">
                <i class="bi bi-arrow-left me-1"></i> Back to appointments
            </a>
        @endif
        <span class="text-muted small">Registered {{ $patient->created_at->format('d M Y') }}</span>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="avatar" style="width: 64px; height: 64px; font-size: 1.25rem;">
                    {{ strtoupper(mb_substr($patient->patient_name, 0, 2)) }}
                </span>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h2 class="fw-bold mb-0">{{ $patient->patient_name }}</h2>
                        <span class="badge badge-soft-primary">{{ $patient->patient_code }}</span>
                        @if ($patient->blood_group)
                            <span class="badge badge-soft-danger">Blood {{ $patient->blood_group }}</span>
                        @endif
                    </div>
                    <div class="text-muted small mt-1">
                        {{ $patient->gender }} · {{ $patient->age }} years · DOB {{ $patient->date_of_birth->format('d M Y') }}
                    </div>
                </div>
            </div>

            <div class="section-divider"></div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value">{{ $patient->phone }}</div>
                </div>
                <div class="col-md-4">
                    <div class="detail-label">Email</div>
                    <div class="detail-value">{{ $patient->email ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="detail-label">Emergency Contact</div>
                    <div class="detail-value">{{ $patient->emergency_contact ?: '—' }}</div>
                </div>
                <div class="col-12">
                    <div class="detail-label">Address</div>
                    <div class="detail-value">{{ $patient->address ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="{{ $user->isAdminOrReceptionist() ? 'col-6 col-xl-3' : 'col-6' }}">
            <div class="card stat-card">
                <div>
                    <div class="stat-label">Appointments</div>
                    <div class="stat-value">{{ number_format($summary['appointments']) }}</div>
                </div>
                <div class="stat-icon teal"><i class="bi bi-calendar2-week-fill"></i></div>
            </div>
        </div>
        @if ($user->isAdmin() || $user->isDoctor())
            <div class="{{ $user->isAdminOrReceptionist() ? 'col-6 col-xl-3' : 'col-6' }}">
                <div class="card stat-card">
                    <div>
                        <div class="stat-label">Prescriptions</div>
                        <div class="stat-value">{{ number_format($summary['prescriptions']) }}</div>
                    </div>
                    <div class="stat-icon navy"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                </div>
            </div>
        @endif
        @if ($user->isAdminOrReceptionist())
            <div class="col-6 col-xl-3">
                <div class="card stat-card">
                    <div>
                        <div class="stat-label">Bills</div>
                        <div class="stat-value">{{ number_format($summary['bills']) }}</div>
                    </div>
                    <div class="stat-icon indigo"><i class="bi bi-receipt-cutoff"></i></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card">
                    <div>
                        <div class="stat-label">Outstanding</div>
                        <div class="stat-value text-danger">Rs {{ number_format($summary['outstanding'], 0) }}</div>
                    </div>
                    <div class="stat-icon rose"><i class="bi bi-exclamation-circle-fill"></i></div>
                </div>
            </div>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title">Appointment History</h2>
            <p class="card-subtitle">Most recent {{ $patient->appointments->count() }} appointment(s)</p>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Doctor</th>
                        <th>Department</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patient->appointments as $appointment)
                        <tr>
                            <td class="fw-semibold">{{ $appointment->appointment_date->format('d M Y') }}</td>
                            <td>{{ $appointment->formatted_time }}</td>
                            <td>{{ $appointment->doctor->doctor_name }}</td>
                            <td>{{ $appointment->doctor->department?->department_name }}</td>
                            <td>{{ $appointment->reason ?: '—' }}</td>
                            <td><span class="badge {{ $appointment->status_badge_class }}">{{ $appointment->status }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state"><i class="bi bi-calendar-x"></i>No appointments yet.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($user->isAdmin() || $user->isDoctor())
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">Prescription History</h2>
                <p class="card-subtitle">Clinical records for this patient</p>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Medicines</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patient->prescriptions as $prescription)
                            <tr>
                                <td class="fw-semibold">{{ $prescription->prescription_date->format('d M Y') }}</td>
                                <td>{{ $prescription->doctor->doctor_name }}</td>
                                <td>{{ $prescription->diagnosis }}</td>
                                <td><span class="badge badge-soft-primary">{{ $prescription->items_count }} item(s)</span></td>
                                <td class="text-end">
                                    <a href="{{ route('prescriptions.show', $prescription) }}" class="btn btn-sm btn-soft" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('prescriptions.print', $prescription) }}" target="_blank" class="btn btn-sm btn-light border" title="Print">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state"><i class="bi bi-clipboard2"></i>No prescriptions recorded.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($user->isAdminOrReceptionist())
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Billing History</h2>
                <p class="card-subtitle">Invoices and payment status</p>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Bill No.</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patient->bills as $bill)
                            <tr>
                                <td class="fw-semibold">{{ $bill->bill_number }}</td>
                                <td>{{ $bill->bill_date->format('d M Y') }}</td>
                                <td>Rs {{ number_format($bill->total_amount, 0) }}</td>
                                <td>Rs {{ number_format($bill->paid_amount, 0) }}</td>
                                <td>Rs {{ number_format($bill->balance_amount, 0) }}</td>
                                <td><span class="badge {{ $bill->payment_status_badge_class }}">{{ $bill->payment_status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('bills.print', $bill) }}" target="_blank" class="btn btn-sm btn-light border" title="Print">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state"><i class="bi bi-receipt"></i>No bills recorded.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
