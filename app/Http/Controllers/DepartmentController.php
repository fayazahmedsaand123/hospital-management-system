<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::query()
            ->withCount('doctors')
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderBy('department_name')
            ->paginate(10)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('departments.partials.table', compact('departments'))->render(),
                'total' => $departments->total(),
            ]);
        }

        return view('departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_name' => ['required', 'string', 'max:100', 'unique:departments,department_name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        Department::create($data);

        return response()->json(['message' => 'Department created successfully.'], 201);
    }

    public function show(Department $department)
    {
        $department->loadCount('doctors');

        return response()->json(['record' => $department]);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'department_name' => [
                'required', 'string', 'max:100',
                Rule::unique('departments', 'department_name')->ignore($department->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $department->update($data);

        return response()->json(['message' => 'Department updated successfully.']);
    }

    public function destroy(Department $department)
    {
        $doctorCount = $department->doctors()->count();

        if ($doctorCount > 0) {
            return response()->json([
                'message' => "This department still has {$doctorCount} doctor(s). Reassign or remove them first.",
            ], 422);
        }

        $department->delete();

        return response()->json(['message' => 'Department deleted successfully.']);
    }
}
