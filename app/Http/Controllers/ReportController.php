<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->user()->role;

        $reports = [
            [
                'title' => 'Patient Register',
                'description' => 'Patients registered in the selected period, with gender and contact details.',
                'icon' => 'bi-people',
                'tone' => 'teal',
                'route' => route('reports.patients'),
                'roles' => ['admin', 'receptionist'],
            ],
            [
                'title' => 'Doctors & Departments',
                'description' => 'Doctor workload: appointments, completed visits and prescriptions.',
                'icon' => 'bi-person-badge',
                'tone' => 'navy',
                'route' => route('reports.doctors'),
                'roles' => ['admin', 'receptionist'],
            ],
            [
                'title' => 'Appointments',
                'description' => 'Appointments in a date range, filtered by doctor and status.',
                'icon' => 'bi-calendar2-week',
                'tone' => 'indigo',
                'route' => route('reports.appointments'),
                'roles' => ['admin', 'receptionist', 'doctor'],
            ],
            [
                'title' => 'Prescriptions',
                'description' => 'Prescriptions written in the selected period and medicines issued.',
                'icon' => 'bi-file-medical',
                'tone' => 'green',
                'route' => route('reports.prescriptions'),
                'roles' => ['admin', 'doctor'],
            ],
            [
                'title' => 'Billing Summary',
                'description' => 'Bills issued, discounts, payments collected and outstanding balance.',
                'icon' => 'bi-receipt',
                'tone' => 'amber',
                'route' => route('reports.billing'),
                'roles' => ['admin', 'receptionist'],
            ],
            [
                'title' => 'Payments Received',
                'description' => 'Bills that received payments, with the amount collected.',
                'icon' => 'bi-cash-coin',
                'tone' => 'green',
                'route' => route('reports.payments'),
                'roles' => ['admin', 'receptionist'],
            ],
            [
                'title' => 'Outstanding Balances',
                'description' => 'Unpaid and partially paid bills with the amount still owed.',
                'icon' => 'bi-exclamation-diamond',
                'tone' => 'rose',
                'route' => route('reports.outstanding'),
                'roles' => ['admin', 'receptionist'],
            ],
        ];

        $reports = array_values(array_filter($reports, fn (array $report) => in_array($role, $report['roles'], true)));

        return view('reports.index', compact('reports'));
    }

    public function patients(Request $request)
    {
        $this->authorizeReport($request, ['admin', 'receptionist']);
        [$from, $to] = $this->dateRange($request);

        $gender = $request->input('gender');
        $search = $request->string('q')->toString();

        $base = Patient::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->search($search);

        $patients = (clone $base)
            ->when($gender, fn ($query) => $query->where('gender', $gender))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => (clone $base)->count(),
            'male' => (clone $base)->where('gender', 'Male')->count(),
            'female' => (clone $base)->where('gender', 'Female')->count(),
            'other' => (clone $base)->where('gender', 'Other')->count(),
        ];

        return $this->respond($request, 'reports.patients', 'reports.partials.patients-table', [
            'patients' => $patients,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function doctors(Request $request)
    {
        $this->authorizeReport($request, ['admin', 'receptionist']);
        [$from, $to] = $this->dateRange($request);

        $departmentId = $request->input('department_id');
        $search = $request->string('q')->toString();

        $base = Doctor::query()
            ->with('department')
            ->search($search)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->withCount([
                'appointments as appointments_count' => fn ($query) => $query
                    ->whereDate('appointment_date', '>=', $from)
                    ->whereDate('appointment_date', '<=', $to),
                'appointments as completed_count' => fn ($query) => $query
                    ->whereDate('appointment_date', '>=', $from)
                    ->whereDate('appointment_date', '<=', $to)
                    ->where('status', 'Completed'),
                'prescriptions as prescriptions_count' => fn ($query) => $query
                    ->whereDate('prescription_date', '>=', $from)
                    ->whereDate('prescription_date', '<=', $to),
            ]);

        $doctors = (clone $base)->orderBy('doctor_name')->paginate(15)->withQueryString();

        $appointmentsInRange = Appointment::query()
            ->whereIn('doctor_id', (clone $base)->select('doctors.id'))
            ->whereDate('appointment_date', '>=', $from)
            ->whereDate('appointment_date', '<=', $to);

        $summary = [
            'total' => (clone $base)->count(),
            'available' => (clone $base)->where('availability_status', 'available')->count(),
            'appointments' => (clone $appointmentsInRange)->count(),
            'completed' => (clone $appointmentsInRange)->where('status', 'Completed')->count(),
        ];

        return $this->respond($request, 'reports.doctors', 'reports.partials.doctors-table', [
            'doctors' => $doctors,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
            'departments' => Department::orderBy('department_name')->get(),
        ]);
    }

    public function appointments(Request $request)
    {
        $this->authorizeReport($request, ['admin', 'receptionist', 'doctor']);
        [$from, $to] = $this->dateRange($request);

        $user = $request->user();
        $isDoctor = $user->isDoctor();
        $ownDoctorId = $this->ownDoctorId($request);

        $base = Appointment::query()
            ->with(['patient', 'doctor'])
            ->whereDate('appointment_date', '>=', $from)
            ->whereDate('appointment_date', '<=', $to)
            ->search($request->string('q')->toString())
            ->when($isDoctor, fn ($query) => $query->where('doctor_id', $ownDoctorId))
            ->when(! $isDoctor && $request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')));

        $appointments = (clone $base)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => (clone $base)->count(),
            'completed' => (clone $base)->where('status', 'Completed')->count(),
            'cancelled' => (clone $base)->where('status', 'Cancelled')->count(),
            'noshow' => (clone $base)->where('status', 'No Show')->count(),
            'upcoming' => (clone $base)->whereIn('status', ['Scheduled', 'Confirmed'])->count(),
        ];

        return $this->respond($request, 'reports.appointments', 'reports.partials.appointments-table', [
            'appointments' => $appointments,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
            'isDoctor' => $isDoctor,
            'doctors' => $isDoctor ? collect() : Doctor::orderBy('doctor_name')->get(),
        ]);
    }

    public function prescriptions(Request $request)
    {
        $this->authorizeReport($request, ['admin', 'doctor']);
        [$from, $to] = $this->dateRange($request);

        $isDoctor = $request->user()->isDoctor();
        $ownDoctorId = $this->ownDoctorId($request);

        $base = Prescription::query()
            ->with(['patient', 'doctor'])
            ->withCount('items')
            ->whereDate('prescription_date', '>=', $from)
            ->whereDate('prescription_date', '<=', $to)
            ->search($request->string('q')->toString())
            ->when($isDoctor, fn ($query) => $query->where('doctor_id', $ownDoctorId))
            ->when(! $isDoctor && $request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')));

        $prescriptions = (clone $base)
            ->orderByDesc('prescription_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => (clone $base)->count(),
            'patients' => (clone $base)->distinct()->count('patient_id'),
            'medicines' => PrescriptionItem::whereIn('prescription_id', (clone $base)->select('prescriptions.id'))->count(),
        ];

        return $this->respond($request, 'reports.prescriptions', 'reports.partials.prescriptions-table', [
            'prescriptions' => $prescriptions,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
            'isDoctor' => $isDoctor,
            'doctors' => $isDoctor ? collect() : Doctor::orderBy('doctor_name')->get(),
        ]);
    }

    public function billing(Request $request)
    {
        $this->authorizeReport($request, ['admin', 'receptionist']);
        [$from, $to] = $this->dateRange($request);

        $base = Bill::query()
            ->whereDate('bill_date', '>=', $from)
            ->whereDate('bill_date', '<=', $to)
            ->search($request->string('q')->toString())
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->input('payment_status')));

        $bills = (clone $base)
            ->with('patient')
            ->withCount('items')
            ->orderByDesc('bill_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => (clone $base)->count(),
            'billed' => (float) (clone $base)->sum('total_amount'),
            'discount' => (float) (clone $base)->sum('discount'),
            'collected' => (float) (clone $base)->sum('paid_amount'),
            'outstanding' => (float) (clone $base)->sum('balance_amount'),
        ];

        return $this->respond($request, 'reports.billing', 'reports.partials.billing-table', [
            'bills' => $bills,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function payments(Request $request)
    {
        $this->authorizeReport($request, ['admin', 'receptionist']);
        [$from, $to] = $this->dateRange($request);

        $base = Bill::query()
            ->where('paid_amount', '>', 0)
            ->whereDate('bill_date', '>=', $from)
            ->whereDate('bill_date', '<=', $to)
            ->search($request->string('q')->toString())
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->input('payment_status')));

        $bills = (clone $base)
            ->with('patient')
            ->orderByDesc('bill_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $collected = (float) (clone $base)->sum('paid_amount');
        $count = (clone $base)->count();

        $summary = [
            'total' => $count,
            'collected' => $collected,
            'average' => $count > 0 ? $collected / $count : 0,
            'partial' => (clone $base)->where('payment_status', 'Partially Paid')->count(),
            'paid' => (clone $base)->where('payment_status', 'Paid')->count(),
        ];

        return $this->respond($request, 'reports.payments', 'reports.partials.payments-table', [
            'bills' => $bills,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function outstanding(Request $request)
    {
        $this->authorizeReport($request, ['admin', 'receptionist']);
        [$from, $to] = $this->dateRange($request);

        $base = Bill::query()
            ->where('balance_amount', '>', 0)
            ->whereDate('bill_date', '>=', $from)
            ->whereDate('bill_date', '<=', $to)
            ->search($request->string('q')->toString())
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->input('payment_status')));

        $bills = (clone $base)
            ->with('patient')
            ->orderBy('bill_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $oldest = (clone $base)->min('bill_date');

        $summary = [
            'total' => (clone $base)->count(),
            'outstanding' => (float) (clone $base)->sum('balance_amount'),
            'unpaid' => (clone $base)->where('payment_status', 'Unpaid')->count(),
            'partial' => (clone $base)->where('payment_status', 'Partially Paid')->count(),
            'oldest' => $oldest ? date('d M Y', strtotime($oldest)) : '—',
        ];

        return $this->respond($request, 'reports.outstanding', 'reports.partials.outstanding-table', [
            'bills' => $bills,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
        ]);
    }

    private function authorizeReport(Request $request, array $roles): void
    {
        abort_unless(in_array($request->user()->role, $roles, true), 403, 'You do not have permission to view this report.');
    }

    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $from = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['date_to'] ?? today()->toDateString();

        return [$from, $to];
    }

    private function ownDoctorId(Request $request): ?int
    {
        if (! $request->user()->isDoctor()) {
            return null;
        }

        $doctorId = $request->user()->doctorProfile?->id;

        abort_unless($doctorId, 403, 'Your account is not linked to a doctor profile.');

        return $doctorId;
    }

    private function respond(Request $request, string $view, string $partial, array $data)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'html' => view($partial, $data)->render(),
                'total' => $data['summary']['total'] ?? 0,
                'summary' => $data['summary'],
            ]);
        }

        return view($view, $data);
    }
}
