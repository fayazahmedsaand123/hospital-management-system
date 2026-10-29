<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isDoctor = $user->isDoctor();
        $doctor = $isDoctor ? Doctor::where('user_id', $user->id)->first() : null;

        $appointments = fn () => Appointment::query()->when(
            $doctor,
            fn ($query) => $query->where('doctor_id', $doctor->id)
        );

        $stats = [
            'patients' => $isDoctor
                ? Patient::whereIn('id', $appointments()->select('patient_id'))->count()
                : Patient::count(),
            'doctors' => Doctor::count(),
            'departments' => Department::count(),
            'today_appointments' => $appointments()->whereDate('appointment_date', today())->count(),
            'completed' => $appointments()->where('status', 'Completed')->count(),
            'upcoming' => $appointments()
                ->whereIn('status', ['Scheduled', 'Confirmed'])
                ->whereDate('appointment_date', '>=', today())
                ->count(),
            'prescriptions' => $isDoctor
                ? Prescription::where('doctor_id', $doctor?->id)->count()
                : Prescription::count(),
            'outstanding' => $isDoctor ? null : (float) Bill::where('balance_amount', '>', 0)->sum('balance_amount'),
            'collected' => $isDoctor ? null : (float) Bill::sum('paid_amount'),
        ];

        $todaySchedule = $appointments()
            ->with(['patient', 'doctor.department'])
            ->whereDate('appointment_date', today())
            ->whereNotIn('status', ['Cancelled', 'No Show'])
            ->orderBy('appointment_time')
            ->get();

        $recentPatients = $isDoctor
            ? Patient::whereIn('id', $appointments()->select('patient_id'))->latest()->take(5)->get()
            : Patient::latest()->take(5)->get();

        $upcomingAppointments = $appointments()
            ->with(['patient', 'doctor'])
            ->whereIn('status', ['Scheduled', 'Confirmed'])
            ->whereDate('appointment_date', '>=', today())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->take(6)
            ->get();

        $recentBills = $isDoctor
            ? collect()
            : Bill::with('patient')->latest('bill_date')->latest('id')->take(5)->get();

        $departmentStats = Department::withCount('doctors')
            ->withCount('appointments')
            ->orderBy('department_name')
            ->get();

        $statusBreakdown = $appointments()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('dashboard', [
            'isDoctor' => $isDoctor,
            'doctor' => $doctor,
            'stats' => $stats,
            'todaySchedule' => $todaySchedule,
            'recentPatients' => $recentPatients,
            'upcomingAppointments' => $upcomingAppointments,
            'recentBills' => $recentBills,
            'departmentStats' => $departmentStats,
            'statusBreakdown' => $statusBreakdown,
        ]);
    }
}
