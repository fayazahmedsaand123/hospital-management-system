<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            // Optional link to a login account, so a doctor only sees their own work.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('doctor_name');
            $table->string('specialization');
            $table->string('phone', 20);
            $table->string('email')->unique();
            $table->string('qualification')->nullable();
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->enum('availability_status', ['available', 'unavailable'])
                ->default('available')
                ->index();
            $table->timestamps();

            $table->index(['department_id', 'doctor_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
