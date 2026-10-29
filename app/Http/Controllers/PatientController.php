<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $patients = Patient::query()
            ->search($request->string('q')->toString())
            ->when($request->filled('gender'), fn ($query) => $query->where('gender', $request->input('gender')))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('patients.partials.table', compact('patients'))->render(),
                'total' => $patients->total(),
            ]);
        }

        return view('patients.index', compact('patients'));
    }

    /**
     * Patient lookup for appointment and billing forms.
     */
    public function options(Request $request)
    {
        $patients = Patient::query()
            ->search($request->string('q')->toString())
            ->orderBy('patient_name')
            ->limit(25)
            ->get()
            ->map(fn (Patient $patient) => [
                'id' => $patient->id,
                'code' => $patient->patient_code,
                'name' => $patient->patient_name,
                'phone' => $patient->phone,
            ]);

        return response()->json(['patients' => $patients]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $patient = DB::transaction(function () use ($data) {
            $data['patient_code'] = Patient::generatePatientCode();

            return Patient::create($data);
        });

        return response()->json([
            'message' => "Patient registered successfully with code {$patient->patient_code}.",
            'record' => $patient,
        ], 201);
    }

    public function update(Request $request, Patient $patient)
    {
        $patient->update($this->validatedData($request));

        return response()->json(['message' => 'Patient updated successfully.']);
    }

    public function show(Request $request, Patient $patient)
    {
        if ($request->user()->isDoctor()) {
            $doctorId = $request->user()->doctorProfile?->id;

            abort_unless(
                $doctorId && $patient->appointments()->where('doctor_id', $doctorId)->exists(),
                403,
                'You can only view patients assigned to you.'
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['record' => $patient]);
        }

        $summary = [
            'appointments' => $patient->appointments()->count(),
            'prescriptions' => $patient->prescriptions()->count(),
            'bills' => $patient->bills()->count(),
            'outstanding' => (float) $patient->bills()->sum('balance_amount'),
        ];

        $patient->load([
            'appointments' => fn ($query) => $query->with('doctor.department')
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->limit(10),
            'prescriptions' => fn ($query) => $query->with('doctor')
                ->withCount('items')
                ->orderByDesc('prescription_date')
                ->limit(10),
            'bills' => fn ($query) => $query->orderByDesc('bill_date')->orderByDesc('id')->limit(10),
        ]);

        return view('patients.show', compact('patient', 'summary'));
    }

    public function destroy(Patient $patient)
    {
        if ($patient->appointments()->exists() || $patient->prescriptions()->exists() || $patient->bills()->exists()) {
            return response()->json([
                'message' => 'This patient has medical or billing history and cannot be deleted.',
            ], 422);
        }

        $patient->delete();

        return response()->json(['message' => 'Patient deleted successfully.']);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'patient_name' => ['required', 'string', 'max:120'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'emergency_contact' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
