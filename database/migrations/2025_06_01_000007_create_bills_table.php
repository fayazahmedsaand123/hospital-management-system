<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            // Keep the bill for financial history even if the appointment is removed.
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bill_number', 30)->unique();
            $table->date('bill_date')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['Unpaid', 'Partially Paid', 'Paid'])
                ->default('Unpaid')
                ->index();
            $table->timestamps();

            $table->index(['patient_id', 'bill_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
