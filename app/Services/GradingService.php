<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GradingService
{
    /**
     * Recalculate a student's weighted final grade in a course.
     */
    public function recalculateStudentGrade(Course $course, User $student, ?User $actor = null, ?string $feedback = null): Grade
    {
        return DB::transaction(function () use ($course, $student, $actor, $feedback) {
            $publishedAssignmentIds = Assignment::query()
                ->where('course_id', $course->id)
                ->where('is_published', true)
                ->pluck('id');

            $assignmentAvg = 0.0;
            if ($publishedAssignmentIds->isNotEmpty()) {
                $submissions = Submission::query()
                    ->whereIn('assignment_id', $publishedAssignmentIds)
                    ->where('student_id', $student->id)
                    ->whereNotNull('score')
                    ->with('assignment:id,max_score')
                    ->get();

                if ($submissions->isNotEmpty()) {
                    $normalizedScores = $submissions->map(function (Submission $sub) {
                        $max = max(1, (float) ($sub->assignment?->max_score ?? 100));

                        return min(100, ((float) $sub->score / $max) * 100);
                    });
                    $assignmentAvg = round($normalizedScores->avg(), 2);
                }
            }

            $quizAvg = $this->calculateQuizCategoryAverage($course->id, $student->id, 'quiz');
            $examAvg = $this->calculateQuizCategoryAverage($course->id, $student->id, 'exam');

            $weightSum = max(1, $course->assignment_weight + $course->quiz_weight + $course->exam_weight);
            $finalScore = round((
                ($assignmentAvg * $course->assignment_weight) +
                ($quizAvg * $course->quiz_weight) +
                ($examAvg * $course->exam_weight)
            ) / $weightSum, 2);

            $existing = Grade::query()
                ->where('course_id', $course->id)
                ->where('student_id', $student->id)
                ->first();

            $grade = Grade::query()->updateOrCreate(
                ['course_id' => $course->id, 'student_id' => $student->id],
                [
                    'assignment_avg' => $assignmentAvg,
                    'quiz_avg' => $quizAvg,
                    'exam_avg' => $examAvg,
                    'final_score' => $finalScore,
                    'letter_grade' => $this->toLetterGrade($finalScore),
                    'instructor_feedback' => $feedback ?? $existing?->instructor_feedback,
                    'updated_by' => $actor?->id,
                ]
            );

            Enrollment::query()
                ->where('course_id', $course->id)
                ->where('student_id', $student->id)
                ->update(['final_score' => $finalScore]);

            return $grade;
        });
    }

    public function recalculateCourseGrades(Course $course, ?User $actor = null): void
    {
        $students = $course->students()->get();
        foreach ($students as $student) {
            $this->recalculateStudentGrade($course, $student, $actor);
        }
    }

    private function calculateQuizCategoryAverage(int $courseId, int $studentId, string $type): float
    {
        $quizIds = Quiz::query()
            ->where('course_id', $courseId)
            ->where('type', $type)
            ->where('is_published', true)
            ->pluck('id');

        if ($quizIds->isEmpty()) {
            return 0.0;
        }

        // Best graded/submitted percentage per quiz
        $bestPerQuiz = QuizAttempt::query()
            ->whereIn('quiz_id', $quizIds)
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'auto_submitted', 'graded'])
            ->selectRaw('quiz_id, MAX(percentage) as best_percentage')
            ->groupBy('quiz_id')
            ->pluck('best_percentage');

        if ($bestPerQuiz->isEmpty()) {
            return 0.0;
        }

        return round((float) $bestPerQuiz->avg(), 2);
    }

    public function toLetterGrade(float $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 80 => 'A-',
            $score >= 75 => 'B+',
            $score >= 70 => 'B',
            $score >= 65 => 'B-',
            $score >= 60 => 'C+',
            $score >= 55 => 'C',
            $score >= 45 => 'D',
            default => 'E',
        };
    }
}
