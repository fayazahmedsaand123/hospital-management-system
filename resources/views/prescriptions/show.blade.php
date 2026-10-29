@extends('layouts.app')

@section('title', 'Prescription #RX-' . str_pad((string) $prescription->id, 5, '0', STR_PAD_LEFT))
@section('page-title', 'Prescription Details')
@section('page-subtitle', $prescription->patient->patient_name . ' · ' . $prescription->prescription_date->format('d M Y'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <a href="{{ url()->previous() }}" class="btn btn-soft">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('patients.show', $prescription->patient) }}" class="btn btn-light border">
                <i class="bi bi-person-badge me-1"></i> Patient profile
            </a>
            <a href="{{ route('prescriptions.print', $prescription) }}" target="_blank" class="btn btn-teal">
                <i class="bi bi-printer me-1"></i> Print
            </a>
        </div>
    </div>

    @include('prescriptions.partials.document')
@endsection
