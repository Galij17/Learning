<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('assignment_avg', 5, 2)->default(0.00);
            $table->decimal('quiz_avg', 5, 2)->default(0.00);
            $table->decimal('exam_avg', 5, 2)->default(0.00);
            $table->decimal('final_score', 5, 2)->default(0.00);
            $table->string('letter_grade', 5)->nullable();
            $table->text('instructor_feedback')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'student_id']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('certificate_number', 64)->unique();
            $table->decimal('final_score', 5, 2);
            $table->string('verification_hash', 64)->unique();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();

            $table->unique(['course_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('grades');
    }
};
