<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $bills = Bill::query()
            ->with(['patient', 'appointment'])
            ->withCount('items')
            ->search($request->string('q')->toString())
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->input('payment_status')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('bill_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('bill_date', '<=', $request->input('date_to')))
            ->orderByDesc('bill_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => Bill::count(),
            'unpaid' => Bill::where('payment_status', 'Unpaid')->count(),
            'outstanding' => (float) Bill::sum('balance_amount'),
            'collected' => (float) Bill::sum('paid_amount'),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('bills.partials.table', compact('bills'))->render(),
                'total' => $bills->total(),
                'summary' => $summary,
            ]);
        }

        return view('bills.index', [
            'bills' => $bills,
            'patients' => Patient::orderBy('patient_name')->get(),
            'paymentStatuses' => Bill::PAYMENT_STATUSES,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $items = $data['items'];
        unset($data['items']);

        $totals = $this->calculateTotals($items, (float) ($data['discount'] ?? 0));
        $this->ensureAppointmentMatches($data['appointment_id'] ?? null, (int) $data['patient_id']);

        $paid = (float) ($data['paid_amount'] ?? 0);

        if ($paid > $totals['total_amount']) {
            throw ValidationException::withMessages([
                'paid_amount' => 'The amount paid cannot be greater than the bill total.',
            ]);
        }

        $bill = DB::transaction(function () use ($data, $items, $totals, $paid) {
            $data = array_merge($data, $totals, [
                'bill_number' => $this->nextBillNumber($data['bill_date']),
                'paid_amount' => round($paid, 2),
                'balance_amount' => round($totals['total_amount'] - $paid, 2),
                'payment_status' => $this->statusFor($totals['total_amount'], $paid),
            ]);

            $bill = Bill::create($data);
            $bill->items()->createMany($items);

            return $bill;
        });

        return response()->json([
            'message' => 'Bill created successfully.',
            'id' => $bill->id,
            'bill_number' => $bill->bill_number,
        ], 201);
    }

    public function update(Request $request, Bill $bill)
    {
        $data = $this->validatedData($request);
        $items = $data['items'];
        unset($data['items'], $data['paid_amount']);

        $discount = (float) ($data['discount'] ?? 0);
        $totals = $this->calculateTotals($items, $discount);
        $this->ensureAppointmentMatches($data['appointment_id'] ?? null, (int) $data['patient_id']);

        // Payments are only changed through the payment endpoint. The edited
        // total must still be able to cover whatever has already been paid.
        $paid = (float) $bill->paid_amount;

        if ($paid > $totals['total_amount']) {
            throw ValidationException::withMessages([
                'discount' => 'The new total (Rs '.number_format($totals['total_amount'], 2).') is lower than the amount already paid (Rs '.number_format($paid, 2).').',
            ]);
        }

        DB::transaction(function () use ($bill, $data, $items, $totals, $paid) {
            $bill->update(array_merge($data, $totals, [
                'balance_amount' => round($totals['total_amount'] - $paid, 2),
                'payment_status' => $this->statusFor($totals['total_amount'], $paid),
            ]));

            $bill->items()->delete();
            $bill->items()->createMany($items);
        });

        return response()->json(['message' => 'Bill updated successfully.']);
    }

    public function show(Request $request, Bill $bill)
    {
        $bill->load(['patient', 'appointment.doctor', 'items']);

        if ($request->expectsJson()) {
            return response()->json(['record' => $bill]);
        }

        return view('bills.show', compact('bill'));
    }

    public function recordPayment(Request $request, Bill $bill)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($bill, $validated) {
            $locked = Bill::whereKey($bill->id)->lockForUpdate()->firstOrFail();

            $balance = (float) $locked->balance_amount;
            $amount = round((float) $validated['amount'], 2);

            if ($balance <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This bill has already been paid in full.',
                ]);
            }

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot exceed the outstanding balance of Rs '.number_format($balance, 2).'.',
                ]);
            }

            $paid = round((float) $locked->paid_amount + $amount, 2);

            $locked->update([
                'paid_amount' => $paid,
                'balance_amount' => round((float) $locked->total_amount - $paid, 2),
                'payment_status' => $this->statusFor((float) $locked->total_amount, $paid),
            ]);
        });

        return response()->json(['message' => 'Payment recorded successfully.']);
    }

    public function destroy(Bill $bill)
    {
        if ((float) $bill->paid_amount > 0) {
            return response()->json([
                'message' => 'This bill has payments recorded and cannot be deleted. Reverse the payment first.',
            ], 422);
        }

        $bill->delete();

        return response()->json(['message' => 'Bill deleted successfully.']);
    }

    public function print(Bill $bill)
    {
        $bill->load(['patient', 'appointment.doctor', 'items']);

        return view('bills.print', compact('bill'));
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'bill_date' => ['required', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function calculateTotals(array &$items, float $discount): array
    {
        $subtotal = 0.0;

        foreach ($items as &$item) {
            $quantity = (int) $item['quantity'];
            $unitPrice = round((float) $item['unit_price'], 2);
            $lineTotal = round($quantity * $unitPrice, 2);

            $item['unit_price'] = $unitPrice;
            $item['line_total'] = $lineTotal;
            $subtotal += $lineTotal;
        }
        unset($item);

        $subtotal = round($subtotal, 2);
        $discount = round($discount, 2);

        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'discount' => 'The discount cannot be greater than the subtotal (Rs '.number_format($subtotal, 2).').',
            ]);
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_amount' => round($subtotal - $discount, 2),
        ];
    }

    private function ensureAppointmentMatches(?int $appointmentId, int $patientId): void
    {
        if (! $appointmentId) {
            return;
        }

        $appointment = Appointment::find($appointmentId);

        if (! $appointment || (int) $appointment->patient_id !== $patientId) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The selected appointment does not belong to this patient.',
            ]);
        }
    }

    private function statusFor(float $total, float $paid): string
    {
        if ($paid <= 0) {
            return 'Unpaid';
        }

        return $paid >= $total ? 'Paid' : 'Partially Paid';
    }

    private function nextBillNumber(string $date): string
    {
        $base = Bill::generateBillNumber($date);
        $candidate = $base;
        $sequence = (int) substr($base, -4);

        while (Bill::where('bill_number', $candidate)->exists()) {
            $sequence++;
            $candidate = 'BILL-'.date('Ymd', strtotime($date)).'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
