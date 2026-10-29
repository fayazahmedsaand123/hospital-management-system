<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            // Restrict deletes so medical history is never orphaned.
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $table->date('appointment_date')->index();
            $table->time('appointment_time');
            $table->string('reason')->nullable();
            $table->enum('status', ['Scheduled', 'Confirmed', 'Completed', 'Cancelled', 'No Show'])
                ->default('Scheduled')
                ->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index (not unique) because cancelled slots may be re-booked;
            // conflicts are validated in a transaction inside the controller.
            $table->index(
                ['doctor_id', 'appointment_date', 'appointment_time'],
                'appointments_doctor_slot_index'
            );
            $table->index(['patient_id', 'appointment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
