<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isDoctor = $user->isDoctor();
        $doctorId = $isDoctor ? $user->doctorProfile?->id : null;

        $appointments = Appointment::query()
            ->with(['patient', 'doctor.department'])
            ->when($isDoctor, fn ($query) => $query->where('doctor_id', $doctorId))
            ->when(! $isDoctor && $request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('department_id'), fn ($query) => $query->whereHas(
                'doctor',
                fn ($doctorQuery) => $doctorQuery->where('department_id', $request->integer('department_id'))
            ))
            ->search($request->string('q')->toString())
            ->when($request->filled('date'), fn ($query) => $query->whereDate('appointment_date', $request->input('date')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(10)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('appointments.partials.table', compact('appointments', 'isDoctor'))->render(),
                'total' => $appointments->total(),
            ]);
        }

        $departments = Department::orderBy('department_name')->get();
        $doctors = Doctor::orderBy('doctor_name')->get();
        $patients = Patient::orderBy('patient_name')->get();

        return view('appointments.index', compact('appointments', 'departments', 'doctors', 'patients', 'isDoctor'));
    }

    /**
     * Appointments of one patient (used by the prescription and billing forms).
     */
    public function options(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
        ]);

        $appointments = Appointment::query()
            ->with('doctor')
            ->where('patient_id', $validated['patient_id'])
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(25)
            ->get()
            ->map(fn (Appointment $appointment) => [
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'label' => $appointment->appointment_date->format('d M Y')
                    .' · '.$appointment->formatted_time
                    .' · '.$appointment->doctor->doctor_name,
                'status' => $appointment->status,
            ]);

        return response()->json(['appointments' => $appointments]);
    }

    /**
     * Free 30-minute consultation slots for a doctor on a given date.
     */
    public function slots(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'date' => ['required', 'date'],
            'exclude' => ['nullable', 'integer'],
        ]);

        $booked = Appointment::query()
            ->activeBooking()
            ->where('doctor_id', $validated['doctor_id'])
            ->whereDate('appointment_date', $validated['date'])
            ->when($request->filled('exclude'), fn ($query) => $query->whereKeyNot($request->integer('exclude')))
            ->pluck('appointment_time')
            ->map(fn ($time) => substr($time, 0, 5))
            ->all();

        $isToday = $validated['date'] === today()->toDateString();
        $currentTime = now()->format('H:i');
        $slots = [];

        for ($hour = 9; $hour <= 16; $hour++) {
            foreach (['00', '30'] as $minute) {
                $time = sprintf('%02d:%s', $hour, $minute);

                if (in_array($time, $booked, true)) {
                    continue;
                }

                if ($isToday && $time <= $currentTime) {
                    continue;
                }

                $slots[] = [
                    'value' => $time,
                    'label' => Carbon::parse($time)->format('g:i A'),
                ];
            }
        }

        return response()->json(['slots' => $slots]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request, true);
        $data['appointment_time'] = $this->normalizeTime($data['appointment_time']);
        $this->ensureDoctorIsAvailable($data['doctor_id']);

        DB::transaction(function () use ($data) {
            $this->ensureSlotIsFree($data['doctor_id'], $data['appointment_date'], $data['appointment_time']);

            Appointment::create($data);
        });

        return response()->json(['message' => 'Appointment booked successfully.'], 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $this->validatedData($request, false);
        $data['appointment_time'] = $this->normalizeTime($data['appointment_time']);

        DB::transaction(function () use ($data, $appointment) {
            if ((int) $appointment->doctor_id !== (int) $data['doctor_id']) {
                $this->ensureDoctorIsAvailable($data['doctor_id']);
            }

            if (in_array($data['status'], ['Scheduled', 'Confirmed', 'Completed'], true)) {
                $this->ensureSlotIsFree(
                    $data['doctor_id'],
                    $data['appointment_date'],
                    $data['appointment_time'],
                    $appointment->id
                );
            }

            $appointment->update($data);
        });

        return response()->json(['message' => 'Appointment updated successfully.']);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if ($request->user()->isDoctor() && ! $this->doctorOwns($request, $appointment)) {
            abort(403, 'You can only view your own appointments.');
        }

        $appointment->load(['patient', 'doctor.department']);

        return response()->json(['record' => $appointment]);
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        if ($request->user()->isDoctor() && ! $this->doctorOwns($request, $appointment)) {
            abort(403, 'You can only update your own appointments.');
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(Appointment::STATUSES)],
        ]);

        $appointment->update($data);

        return response()->json(['message' => 'Appointment status updated to '.$data['status'].'.']);
    }

    public function destroy(Appointment $appointment)
    {
        if ($appointment->prescription()->exists() || $appointment->bill()->exists()) {
            return response()->json([
                'message' => 'This appointment has linked prescriptions or bills and cannot be deleted.',
            ], 422);
        }

        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted successfully.']);
    }

    private function validatedData(Request $request, bool $isNew): array
    {
        return $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'appointment_date' => $isNew
                ? ['required', 'date', 'after_or_equal:today']
                : ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Appointment::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function ensureDoctorIsAvailable(int $doctorId): void
    {
        $available = Doctor::whereKey($doctorId)
            ->where('availability_status', 'available')
            ->exists();

        if (! $available) {
            throw ValidationException::withMessages([
                'doctor_id' => 'The selected doctor is currently unavailable for new appointments.',
            ]);
        }
    }

    /**
     * Must run inside a transaction. Cancelled / no-show slots may be re-used.
     */
    private function ensureSlotIsFree(int $doctorId, string $date, string $time, ?int $ignoreId = null): void
    {
        $conflict = Appointment::query()
            ->activeBooking()
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('appointment_time', $time)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->lockForUpdate()
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'appointment_time' => 'This doctor already has an appointment on that date and time.',
            ]);
        }
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function doctorOwns(Request $request, Appointment $appointment): bool
    {
        $doctorId = $request->user()->doctorProfile?->id;

        return $doctorId && $appointment->doctor_id === $doctorId;
    }
}
