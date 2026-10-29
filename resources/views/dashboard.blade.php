@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . auth()->user()->name)

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value">{{ number_format($stats['patients']) }}</div>
                    <div class="stat-hint">{{ $isDoctor ? 'Patients you have seen' : 'Registered in the system' }}</div>
                </div>
                <div class="stat-icon teal"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div>
                    <div class="stat-label">Total Doctors</div>
                    <div class="stat-value">{{ number_format($stats['doctors']) }}</div>
                    <div class="stat-hint">Across all departments</div>
                </div>
                <div class="stat-icon navy"><i class="bi bi-person-badge-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div>
                    <div class="stat-label">Departments</div>
                    <div class="stat-value">{{ number_format($stats['departments']) }}</div>
                    <div class="stat-hint">Active hospital units</div>
                </div>
                <div class="stat-icon indigo"><i class="bi bi-diagram-3-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div>
                    <div class="stat-label">Today's Appointments</div>
                    <div class="stat-value">{{ number_format($stats['today_appointments']) }}</div>
                    <div class="stat-hint">{{ now()->format('D, d M Y') }}</div>
                </div>
                <div class="stat-icon amber"><i class="bi bi-calendar2-check-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div>
                    <div class="stat-label">Completed</div>
                    <div class="stat-value">{{ number_format($stats['completed']) }}</div>
                    <div class="stat-hint">All-time completed visits</div>
                </div>
                <div class="stat-icon green"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div>
                    <div class="stat-label">Upcoming</div>
                    <div class="stat-value">{{ number_format($stats['upcoming']) }}</div>
                    <div class="stat-hint">Scheduled and confirmed</div>
                </div>
                <div class="stat-icon teal"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>

        @if ($isDoctor)
            <div class="col-12 col-xl-6">
                <div class="card stat-card">
                    <div>
                        <div class="stat-label">Prescriptions Written</div>
                        <div class="stat-value">{{ number_format($stats['prescriptions']) }}</div>
                        <div class="stat-hint">Medical records created by you</div>
                    </div>
                    <div class="stat-icon navy"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                </div>
            </div>
        @else
            <div class="col-6 col-xl-3">
                <div class="card stat-card">
                    <div>
                        <div class="stat-label">Outstanding Bills</div>
                        <div class="stat-value text-danger">Rs {{ number_format($stats['outstanding'], 0) }}</div>
                        <div class="stat-hint">Pending patient balances</div>
                    </div>
                    <div class="stat-icon rose"><i class="bi bi-exclamation-circle-fill"></i></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card">
                    <div>
                        <div class="stat-label">Payments Collected</div>
                        <div class="stat-value text-success">Rs {{ number_format($stats['collected'], 0) }}</div>
                        <div class="stat-hint">Lifetime collections</div>
                    </div>
                    <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
                </div>
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="card-title">Today's Schedule</h2>
                        <p class="card-subtitle">{{ $todaySchedule->count() }} appointment(s) for {{ now()->format('l, d F Y') }}</p>
                    </div>
                    <i class="bi bi-calendar2-week fs-5 text-secondary"></i>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                @unless ($isDoctor)
                                    <th>Doctor</th>
                                @endunless
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($todaySchedule as $appointment)
                                <tr>
                                    <td class="fw-semibold">{{ $appointment->formatted_time }}</td>
                                    <td>
                                        <a href="{{ route('patients.show', $appointment->patient) }}" class="fw-semibold text-decoration-none">
                                            {{ $appointment->patient->patient_name }}
                                        </a>
                                        <div class="text-muted small">{{ $appointment->patient->patient_code }}</div>
                                    </td>
                                    @unless ($isDoctor)
                                        <td>
                                            {{ $appointment->doctor->doctor_name }}
                                            <div class="text-muted small">{{ $appointment->doctor->department->department_name }}</div>
                                        </td>
                                    @endunless
                                    <td>{{ $appointment->reason ?: '—' }}</td>
                                    <td><span class="badge {{ $appointment->status_badge_class }}">{{ $appointment->status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isDoctor ? 4 : 5 }}">
                                        <div class="empty-state">
                                            <i class="bi bi-calendar-x"></i>
                                            No appointments scheduled for today.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="card-title">Upcoming Appointments</h2>
                        <p class="card-subtitle">Next confirmed and scheduled visits</p>
                    </div>
                    <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-soft">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                @unless ($isDoctor)
                                    <th>Doctor</th>
                                @endunless
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($upcomingAppointments as $appointment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $appointment->appointment_date->format('d M Y') }}</div>
                                        <div class="text-muted small">{{ $appointment->formatted_time }}</div>
                                    </td>
                                    <td>
                                        {{ $appointment->patient->patient_name }}
                                        <div class="text-muted small">{{ $appointment->patient->patient_code }}</div>
                                    </td>
                                    @unless ($isDoctor)
                                        <td>{{ $appointment->doctor->doctor_name }}</td>
                                    @endunless
                                    <td><span class="badge {{ $appointment->status_badge_class }}">{{ $appointment->status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isDoctor ? 3 : 4 }}">
                                        <div class="empty-state">
                                            <i class="bi bi-calendar-plus"></i>
                                            No upcoming appointments.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h2 class="card-title">Recent Patient Registrations</h2>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($recentPatients as $patient)
                        <a href="{{ route('patients.show', $patient) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $patient->patient_name }}</div>
                                <div class="text-muted small">{{ $patient->patient_code }} · {{ $patient->gender }} · {{ $patient->age }} yrs</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                    @empty
                        <div class="empty-state"><i class="bi bi-people"></i>No patients yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h2 class="card-title">Department Overview</h2>
                </div>
                <div class="card-body">
                    @php($maxAppointments = max(1, (int) $departmentStats->max('appointments_count')))
                    @foreach ($departmentStats as $department)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold">{{ $department->department_name }}</span>
                                <span class="text-muted">
                                    {{ $department->doctors_count }} doctors · {{ $department->appointments_count }} visits
                                </span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: {{ round(($department->appointments_count / $maxAppointments) * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h2 class="card-title">Appointment Status</h2>
                </div>
                <div class="card-body">
                    @foreach (\App\Models\Appointment::STATUSES as $status)
                        <div class="summary-line">
                            <span class="text-muted">{{ $status }}</span>
                            <strong>{{ number_format($statusBreakdown[$status] ?? 0) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            @unless ($isDoctor)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="card-title">Recent Billing Activity</h2>
                        <a href="{{ route('bills.index') }}" class="btn btn-sm btn-soft">View all</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($recentBills as $bill)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $bill->bill_number }}</div>
                                    <div class="text-muted small">{{ $bill->patient->patient_name }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">Rs {{ number_format($bill->total_amount, 0) }}</div>
                                    <span class="badge {{ $bill->payment_status_badge_class }}">{{ $bill->payment_status }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state"><i class="bi bi-receipt"></i>No bills recorded.</div>
                        @endforelse
                    </div>
                </div>
            @endunless
        </div>
    </div>
@endsection
