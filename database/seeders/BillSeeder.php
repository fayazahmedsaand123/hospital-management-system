<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Bill;
use Illuminate\Database\Seeder;

class BillSeeder extends Seeder
{
    public function run(): void
    {
        if (Bill::exists()) {
            return;
        }

        $appointments = Appointment::with(['doctor'])
            ->where('status', 'Completed')
            ->orderBy('appointment_date')
            ->get();

        $services = [
            ['description' => 'Laboratory Tests', 'price' => 1200],
            ['description' => 'ECG', 'price' => 800],
            ['description' => 'X-Ray', 'price' => 1500],
            ['description' => 'Pharmacy Charges', 'price' => 600],
            ['description' => 'Dressing', 'price' => 500],
            ['description' => 'Ultrasound', 'price' => 2000],
        ];

        $sequences = [];

        foreach ($appointments as $index => $appointment) {
            if (Bill::where('appointment_id', $appointment->id)->exists()) {
                continue;
            }

            $date = $appointment->appointment_date->format('Y-m-d');
            $sequences[$date] = ($sequences[$date] ?? Bill::whereDate('bill_date', $date)->count()) + 1;

            $items = [
                [
                    'item_description' => 'Consultation Fee',
                    'quantity' => 1,
                    'unit_price' => (float) $appointment->doctor->consultation_fee,
                ],
            ];

            if ($index % 2 === 0) {
                $service = $services[$index % count($services)];
                $items[] = [
                    'item_description' => $service['description'],
                    'quantity' => 1,
                    'unit_price' => $service['price'],
                ];
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $discount = [0, 0, 0, 100, 200, 500][$index % 6];
            $discount = min($discount, $subtotal);
            $total = $subtotal - $discount;

            $paid = match ($index % 5) {
                0, 1 => 0.0,
                2, 3 => round($total / 2, 2),
                default => $total,
            };

            $balance = round($total - $paid, 2);

            $paymentStatus = $paid <= 0
                ? 'Unpaid'
                : ($paid >= $total ? 'Paid' : 'Partially Paid');

            $bill = Bill::create([
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'bill_number' => 'BILL-'.date('Ymd', strtotime($date)).'-'.str_pad((string) $sequences[$date], 4, '0', STR_PAD_LEFT),
                'bill_date' => $date,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'balance_amount' => $balance,
                'payment_status' => $paymentStatus,
            ]);

            foreach ($items as $item) {
                $bill->items()->create([
                    'item_description' => $item['item_description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        }
    }
}
