<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->date('date_of_birth');
            $table->string('email');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone');
            $table->string('address');
            $table->text('notes')->nullable();
            $table->enum('status', ['applied', 'screening', 'pending_interview', 'pending_offer', 'offered', 'rejected', 'accepted', 'declined'])->default('applied');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};