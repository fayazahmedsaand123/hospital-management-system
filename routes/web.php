<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/lookups/doctors', [DoctorController::class, 'options'])->name('lookups.doctors');
    Route::get('/lookups/patients', [PatientController::class, 'options'])->name('lookups.patients');
    Route::get('/lookups/appointments', [AppointmentController::class, 'options'])->name('lookups.appointments');
    Route::get('/lookups/slots', [AppointmentController::class, 'slots'])->name('lookups.slots');

    // Admin + receptionist shared screens.
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');

        Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
        Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');

        Route::resource('patients', PatientController::class)->except(['create', 'edit', 'destroy', 'show']);

        Route::resource('bills', BillController::class)->except(['create', 'edit', 'destroy']);
        Route::post('/bills/{bill}/payments', [BillController::class, 'recordPayment'])->name('bills.payments.store');
        Route::get('/bills/{bill}/print', [BillController::class, 'print'])->name('bills.print');
    });

    // Doctors may view the profile of patients assigned to them.
    Route::get('/patients/{patient}', [PatientController::class, 'show'])
        ->middleware('role:admin,receptionist,doctor')
        ->name('patients.show');

    // Administrator-only changes.
    Route::middleware('role:admin')->group(function () {
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::post('/doctors', [DoctorController::class, 'store'])->name('doctors.store');
        Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
        Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy'])->name('doctors.destroy');

        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');

        Route::delete('/bills/{bill}', [BillController::class, 'destroy'])->name('bills.destroy');
    });

    // Appointments: doctors may view and update status of their own records.
    Route::middleware('role:admin,receptionist,doctor')->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    });

    Route::middleware('role:admin,receptionist')->group(function () {
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
        Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('appointments.destroy');
    });

    // Prescriptions: admin and doctors only.
    Route::middleware('role:admin,doctor')->group(function () {
        Route::get('/prescriptions', [PrescriptionController::class, 'index'])->name('prescriptions.index');
        Route::post('/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
        Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])->name('prescriptions.show');
        Route::get('/prescriptions/{prescription}/print', [PrescriptionController::class, 'print'])->name('prescriptions.print');
        Route::put('/prescriptions/{prescription}', [PrescriptionController::class, 'update'])->name('prescriptions.update');
    });

    Route::delete('/prescriptions/{prescription}', [PrescriptionController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('prescriptions.destroy');

    // Reports (each report checks its own role access).
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/patients', [ReportController::class, 'patients'])->name('reports.patients');
    Route::get('/reports/doctors', [ReportController::class, 'doctors'])->name('reports.doctors');
    Route::get('/reports/appointments', [ReportController::class, 'appointments'])->name('reports.appointments');
    Route::get('/reports/prescriptions', [ReportController::class, 'prescriptions'])->name('reports.prescriptions');
    Route::get('/reports/billing', [ReportController::class, 'billing'])->name('reports.billing');
    Route::get('/reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
    Route::get('/reports/outstanding', [ReportController::class, 'outstanding'])->name('reports.outstanding');
});
