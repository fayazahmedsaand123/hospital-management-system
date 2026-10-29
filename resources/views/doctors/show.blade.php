@extends('layouts.app')

@section('title', $doctor->doctor_name)
@section('page-title', 'Doctor Profile')
@section('page-subtitle', $doctor->doctor_name . ' · ' . ($doctor->department?->department_name ?? 'No department'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('doctors.index') }}" class="btn btn-soft">
            <i class="bi bi-arrow-left me-1"></i> Back to doctors
        </a>
        <a href="{{ route('appointments.index', ['doctor_id' => $doctor->id]) }}" class="btn btn-teal">
            <i class="bi bi-calendar2-plus me-1"></i> Doctor appointments
        </a>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar" style="width: 56px; height: 56px; font-size: 1.1rem;">
                            {{ strtoupper(mb_substr($doctor->doctor_name, 0, 2)) }}
                        </span>
                        <div>
                            <div class="fw-bold fs-5">{{ $doctor->doctor_name }}</div>
                            <div class="text-muted small">{{ $doctor->specialization }}</div>
                            <span class="badge {{ $doctor->availability_status === 'available' ? 'badge-soft-success' : 'badge-soft-secondary' }} mt-1">
                                {{ ucfirst($doctor->availability_status) }}
                            </span>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="detail-label">Department</div>
                            <div class="detail-value">{{ $doctor->department?->department_name ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="detail-label">Consultation Fee</div>
                            <div class="detail-value">Rs {{ number_format($doctor->consultation_fee, 0) }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Qualification</div>
                            <div class="detail-value">{{ $doctor->qualification ?: '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value">{{ $doctor->phone }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">{{ $doctor->email }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Upcoming Appointments</h2>
                    <p class="card-subtitle">Scheduled and confirmed visits from today onward</p>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($doctor->upcomingAppointments->take(10) as $appointment)
                                <tr>
                                    <td class="fw-semibold">{{ $appointment->appointment_date->format('d M Y') }}</td>
                                    <td>{{ $appointment->formatted_time }}</td>
                                    <td>
                                        {{ $appointment->patient->patient_name }}
                                        <div class="text-muted small">{{ $appointment->patient->patient_code }}</div>
                                    </td>
                                    <td>{{ $appointment->reason ?: '—' }}</td>
                                    <td><span class="badge {{ $appointment->status_badge_class }}">{{ $appointment->status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="bi bi-calendar-x"></i>
                                            No upcoming appointments for this doctor.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
