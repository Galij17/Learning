<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->enum('type', ['quiz', 'exam'])->default('quiz')->index();
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->unsignedInteger('max_attempts')->default(1);
            $table->decimal('passing_score', 5, 2)->default(70.00);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->boolean('show_results')->default(true);
            $table->boolean('show_explanations')->default(true);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'type', 'is_published']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['multiple_choice', 'true_false', 'essay'])->index();
            $table->longText('question_text');
            $table->text('explanation')->nullable();
            $table->decimal('points', 6, 2)->default(10.00);
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->boolean('is_bank')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'subject_id', 'type']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });

        Schema::create('quiz_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('points', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['quiz_id', 'question_id']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->enum('status', ['in_progress', 'submitted', 'auto_submitted', 'graded'])->default('in_progress')->index();
            $table->json('question_order')->nullable();
            $table->json('option_orders')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('objective_score', 6, 2)->default(0.00);
            $table->decimal('essay_score', 6, 2)->default(0.00);
            $table->decimal('total_score', 6, 2)->default(0.00);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->boolean('is_passed')->default(false);
            $table->boolean('needs_manual_grading')->default(false)->index();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['quiz_id', 'student_id', 'attempt_number']);
            $table->index(['quiz_id', 'student_id', 'status']);
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->longText('essay_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score_awarded', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_question');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');
    }
};
