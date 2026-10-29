<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $doctors = Doctor::query()
            ->with('department')
            ->withCount('appointments')
            ->search($request->string('q')->toString())
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('availability_status'), fn ($query) => $query->where('availability_status', $request->input('availability_status')))
            ->orderBy('doctor_name')
            ->paginate(10)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('doctors.partials.table', compact('doctors'))->render(),
                'total' => $doctors->total(),
            ]);
        }

        $departments = Department::orderBy('department_name')->get();

        return view('doctors.index', compact('doctors', 'departments'));
    }

    /**
     * Lightweight doctor list used by the appointment and prescription forms.
     */
    public function options(Request $request)
    {
        $doctors = Doctor::query()
            ->with('department')
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->orderBy('doctor_name')
            ->get()
            ->map(fn (Doctor $doctor) => [
                'id' => $doctor->id,
                'name' => $doctor->doctor_name,
                'specialization' => $doctor->specialization,
                'fee' => (float) $doctor->consultation_fee,
                'available' => $doctor->availability_status === 'available',
                'department' => $doctor->department?->department_name,
            ]);

        return response()->json(['doctors' => $doctors]);
    }

    public function show(Request $request, Doctor $doctor)
    {
        if ($request->expectsJson()) {
            $doctor->load('department');

            return response()->json(['record' => $doctor]);
        }

        $doctor->load(['department', 'upcomingAppointments.patient']);

        return view('doctors.show', compact('doctor'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Doctor::create($data);

        return response()->json(['message' => 'Doctor added successfully.'], 201);
    }

    public function update(Request $request, Doctor $doctor)
    {
        $data = $this->validatedData($request, $doctor);

        $doctor->update($data);

        return response()->json(['message' => 'Doctor updated successfully.']);
    }

    public function destroy(Doctor $doctor)
    {
        if ($doctor->appointments()->exists() || $doctor->prescriptions()->exists()) {
            return response()->json([
                'message' => 'This doctor has appointments or prescriptions on record and cannot be deleted. Mark them unavailable instead.',
            ], 422);
        }

        $doctor->delete();

        return response()->json(['message' => 'Doctor deleted successfully.']);
    }

    private function validatedData(Request $request, ?Doctor $doctor = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'doctor_name' => ['required', 'string', 'max:120'],
            'specialization' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('doctors', 'email')->ignore($doctor?->id),
            ],
            'qualification' => ['nullable', 'string', 'max:150'],
            'consultation_fee' => ['required', 'numeric', 'min:0', 'max:999999'],
            'availability_status' => ['required', 'in:available,unavailable'],
        ]);
    }
}
