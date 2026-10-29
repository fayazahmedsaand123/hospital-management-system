<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isDoctor = $user->isDoctor();
        $doctorId = $isDoctor ? $user->doctorProfile?->id : null;

        $prescriptions = Prescription::query()
            ->with(['patient', 'doctor', 'appointment'])
            ->withCount('items')
            ->when($isDoctor, fn ($query) => $query->where('doctor_id', $doctorId))
            ->when(! $isDoctor && $request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->search($request->string('q')->toString())
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('prescription_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('prescription_date', '<=', $request->input('date_to')))
            ->orderByDesc('prescription_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('prescriptions.partials.table', compact('prescriptions', 'isDoctor'))->render(),
                'total' => $prescriptions->total(),
            ]);
        }

        return view('prescriptions.index', [
            'prescriptions' => $prescriptions,
            'isDoctor' => $isDoctor,
            'doctors' => Doctor::orderBy('doctor_name')->get(),
            'patients' => Patient::orderBy('patient_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $this->validatedData($request);
        $items = $data['items'];
        unset($data['items']);

        if ($user->isDoctor()) {
            $data['doctor_id'] = $user->doctorProfile?->id;

            abort_unless($data['doctor_id'], 403, 'Your account is not linked to a doctor profile.');
        }

        $this->ensureAppointmentMatches($data['appointment_id'] ?? null, (int) $data['patient_id'], (int) $data['doctor_id']);

        $prescription = DB::transaction(function () use ($data, $items) {
            $prescription = Prescription::create($data);
            $prescription->items()->createMany($items);

            return $prescription;
        });

        return response()->json([
            'message' => 'Prescription saved successfully.',
            'id' => $prescription->id,
        ], 201);
    }

    public function update(Request $request, Prescription $prescription)
    {
        $user = $request->user();

        if ($user->isDoctor() && $prescription->doctor_id !== $user->doctorProfile?->id) {
            abort(403, 'You can only edit your own prescriptions.');
        }

        $data = $this->validatedData($request);
        $items = $data['items'];
        unset($data['items']);

        if ($user->isDoctor()) {
            $data['doctor_id'] = $user->doctorProfile?->id;
        }

        $this->ensureAppointmentMatches($data['appointment_id'] ?? null, (int) $data['patient_id'], (int) $data['doctor_id']);

        DB::transaction(function () use ($prescription, $data, $items) {
            $prescription->update($data);
            $prescription->items()->delete();
            $prescription->items()->createMany($items);
        });

        return response()->json(['message' => 'Prescription updated successfully.']);
    }

    public function show(Request $request, Prescription $prescription)
    {
        if ($request->user()->isDoctor() && $prescription->doctor_id !== $request->user()->doctorProfile?->id) {
            abort(403, 'You can only view your own prescriptions.');
        }

        $prescription->load(['patient', 'doctor.department', 'appointment', 'items']);

        if ($request->expectsJson()) {
            return response()->json(['record' => $prescription]);
        }

        return view('prescriptions.show', compact('prescription'));
    }

    public function print(Prescription $prescription)
    {
        $user = request()->user();

        if ($user->isDoctor() && $prescription->doctor_id !== $user->doctorProfile?->id) {
            abort(403, 'You can only print your own prescriptions.');
        }

        $prescription->load(['patient', 'doctor.department', 'appointment', 'items']);

        return view('prescriptions.print', compact('prescription'));
    }

    public function destroy(Prescription $prescription)
    {
        $prescription->delete();

        return response()->json(['message' => 'Prescription deleted successfully.']);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => $request->user()->isDoctor()
                ? ['nullable']
                : ['required', 'exists:doctors,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'prescription_date' => ['required', 'date'],
            'diagnosis' => ['required', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_name' => ['required', 'string', 'max:150'],
            'items.*.dosage' => ['required', 'string', 'max:100'],
            'items.*.frequency' => ['required', 'string', 'max:100'],
            'items.*.duration' => ['required', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function ensureAppointmentMatches(?int $appointmentId, int $patientId, int $doctorId): void
    {
        if (! $appointmentId) {
            return;
        }

        $appointment = Appointment::find($appointmentId);

        $belongsToPatient = $appointment && (int) $appointment->patient_id === $patientId;
        $belongsToDoctor = $appointment && (int) $appointment->doctor_id === $doctorId;

        if (! $belongsToPatient) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The selected appointment does not belong to this patient.',
            ]);
        }

        if (! $belongsToDoctor) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The selected appointment belongs to a different doctor.',
            ]);
        }
    }
}
