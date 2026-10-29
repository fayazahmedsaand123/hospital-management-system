@extends('layouts.app')

@section('title', 'Bill ' . $bill->bill_number)
@section('page-title', 'Bill Details')
@section('page-subtitle', $bill->bill_number . ' · ' . $bill->patient->patient_name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <a href="{{ route('bills.index') }}" class="btn btn-soft">
            <i class="bi bi-arrow-left me-1"></i> Back to billing
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('patients.show', $bill->patient) }}" class="btn btn-light border">
                <i class="bi bi-person-badge me-1"></i> Patient profile
            </a>
            <a href="{{ route('bills.print', $bill) }}" target="_blank" class="btn btn-teal">
                <i class="bi bi-printer me-1"></i> Print invoice
            </a>
        </div>
    </div>

    @include('bills.partials.document')
@endsection
