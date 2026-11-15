<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->string('code', 16)->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->unsignedTinyInteger('assignment_weight')->default(30);
            $table->unsignedTinyInteger('quiz_weight')->default(30);
            $table->unsignedTinyInteger('exam_weight')->default(40);
            $table->unsignedTinyInteger('min_completion_percentage')->default(80);
            $table->decimal('min_passing_score', 5, 2)->default(70.00);
            $table->boolean('certificate_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['instructor_id', 'status']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'dropped'])->default('active')->index();
            $table->decimal('progress_percentage', 5, 2)->default(0.00);
            $table->decimal('final_score', 5, 2)->nullable();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('courses');
    }
};
