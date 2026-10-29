<?php

namespace Tests\Feature;

use App\Models\Bill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesHospitalRecords;
use Tests\TestCase;

class BillTest extends TestCase
{
    use CreatesHospitalRecords, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'patient_id' => $this->makePatient()->id,
            'bill_date' => today()->toDateString(),
            'discount' => 100,
            'items' => [
                ['item_description' => 'Consultation fee', 'quantity' => 2, 'unit_price' => 500],
                ['item_description' => 'Lab test', 'quantity' => 1, 'unit_price' => 300],
            ],
        ], $overrides);
    }

    public function test_bill_totals_and_line_totals_are_calculated_on_the_server(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $payload = $this->payload();

        $response = $this->actingAs($receptionist)
            ->postJson(route('bills.store'), $payload)
            ->assertCreated();

        $bill = Bill::with('items')->findOrFail($response->json('id'));

        $this->assertEquals(1300, (float) $bill->subtotal);
        $this->assertEquals(100, (float) $bill->discount);
        $this->assertEquals(1200, (float) $bill->total_amount);
        $this->assertEquals(0, (float) $bill->paid_amount);
        $this->assertEquals(1200, (float) $bill->balance_amount);
        $this->assertSame('Unpaid', $bill->payment_status);
        $this->assertStringStartsWith('BILL-', $bill->bill_number);

        $this->assertEquals(1000, (float) $bill->items[0]->line_total);
        $this->assertEquals(300, (float) $bill->items[1]->line_total);
    }

    public function test_bill_numbers_increment_for_the_same_day(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $first = $this->actingAs($receptionist)->postJson(route('bills.store'), $this->payload())->json('bill_number');
        $second = $this->actingAs($receptionist)->postJson(route('bills.store'), $this->payload())->json('bill_number');

        $this->assertNotSame($first, $second);
        $this->assertStringEndsWith('-0001', $first);
        $this->assertStringEndsWith('-0002', $second);
    }

    public function test_discount_cannot_exceed_the_subtotal(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)
            ->postJson(route('bills.store'), $this->payload(['discount' => 5000]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount');
    }

    public function test_payments_cannot_exceed_the_outstanding_balance(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $billId = $this->actingAs($receptionist)->postJson(route('bills.store'), $this->payload())->json('id');
        $bill = Bill::findOrFail($billId);

        $this->actingAs($receptionist)
            ->postJson(route('bills.payments.store', $bill), ['amount' => 500])
            ->assertOk();

        $bill->refresh();
        $this->assertEquals(500, (float) $bill->paid_amount);
        $this->assertEquals(700, (float) $bill->balance_amount);
        $this->assertSame('Partially Paid', $bill->payment_status);

        $this->actingAs($receptionist)
            ->postJson(route('bills.payments.store', $bill), ['amount' => 800])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');

        $this->assertEquals(500, (float) $bill->fresh()->paid_amount);
    }

    public function test_full_payment_marks_the_bill_as_paid(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $bill = Bill::findOrFail(
            $this->actingAs($receptionist)->postJson(route('bills.store'), $this->payload())->json('id')
        );

        $this->actingAs($receptionist)
            ->postJson(route('bills.payments.store', $bill), ['amount' => 1200])
            ->assertOk();

        $bill->refresh();
        $this->assertEquals(0, (float) $bill->balance_amount);
        $this->assertSame('Paid', $bill->payment_status);
    }

    public function test_total_cannot_be_edited_below_the_amount_already_paid(): void
    {
        $admin = $this->makeUser('admin');
        $bill = Bill::findOrFail(
            $this->actingAs($admin)->postJson(route('bills.store'), $this->payload())->json('id')
        );

        $this->actingAs($admin)
            ->postJson(route('bills.payments.store', $bill), ['amount' => 700])
            ->assertOk();

        $this->actingAs($admin)
            ->putJson(route('bills.update', $bill), [
                'patient_id' => $bill->patient_id,
                'bill_date' => $bill->bill_date->toDateString(),
                'discount' => 0,
                'items' => [
                    ['item_description' => 'Small service', 'quantity' => 1, 'unit_price' => 100],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount');
    }

    public function test_bills_with_payments_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('admin');
        $bill = Bill::findOrFail(
            $this->actingAs($admin)->postJson(route('bills.store'), $this->payload())->json('id')
        );

        $this->actingAs($admin)
            ->postJson(route('bills.payments.store', $bill), ['amount' => 100])
            ->assertOk();

        $this->actingAs($admin)
            ->deleteJson(route('bills.destroy', $bill))
            ->assertStatus(422);
    }

    public function test_admin_can_delete_an_unpaid_bill_but_receptionist_cannot(): void
    {
        $admin = $this->makeUser('admin');
        $receptionist = $this->makeUser('receptionist');

        $bill = Bill::findOrFail(
            $this->actingAs($receptionist)->postJson(route('bills.store'), $this->payload())->json('id')
        );

        $this->actingAs($receptionist)
            ->deleteJson(route('bills.destroy', $bill))
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson(route('bills.destroy', $bill))
            ->assertOk();

        $this->assertDatabaseMissing('bills', ['id' => $bill->id]);
    }
}
