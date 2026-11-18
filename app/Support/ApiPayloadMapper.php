<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;

final class ApiPayloadMapper
{
    public static function user(User $user): array
    {
        $user->loadMissing('roles.permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->primaryRole(),
            'identity_number' => $user->identity_number,
            'phone' => $user->phone,
            'bio' => $user->bio,
            'avatar_url' => $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null,
            'is_active' => (bool) $user->is_active,
            'permissions' => $user->roles->flatMap->permissions->pluck('name')->unique()->values()->all(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];
    }

    public static function course(Course $course, ?float $progress = null, ?string $enrollmentStatus = null): array
    {
        $course->loadMissing(['category:id,name', 'subject:id,name', 'instructor:id,name']);

        return [
            'id' => $course->id,
            'title' => $course->title,
            'code' => $course->code,
            'description' => $course->description,
            'thumbnail_url' => $course->thumbnail_path ? Storage::disk('public')->url($course->thumbnail_path) : null,
            'category_name' => $course->category?->name ?? 'Umum',
            'subject_name' => $course->subject?->name,
            'instructor_id' => $course->instructor_id,
            'instructor_name' => $course->instructor?->name ?? '',
            'status' => $course->status,
            'enrollment_status' => $enrollmentStatus ?? 'active',
            'students_count' => (int) ($course->enrollments_count ?? $course->students_count ?? $course->enrollments()->count()),
            'materials_count' => (int) ($course->materials_count ?? $course->materials()->count()),
            'progress_percentage' => $progress ?? 0.0,
            'assignment_weight' => (int) $course->assignment_weight,
            'quiz_weight' => (int) $course->quiz_weight,
            'exam_weight' => (int) $course->exam_weight,
            'certificate_enabled' => (bool) $course->certificate_enabled,
            'updated_at' => $course->updated_at?->toIso8601String(),
        ];
    }

    public static function material(Material $material, bool $isCompleted = false): array
    {
        return [
            'id' => $material->id,
            'lesson_id' => $material->lesson_id,
            'course_id' => $material->course_id,
            'title' => $material->title,
            'type' => $material->type,
            'file_url' => $material->file_path ? url("/api/materials/{$material->id}/stream") : null,
            'file_name' => $material->file_name,
            'file_size' => $material->file_size,
            'video_url' => $material->video_url,
            'content' => $material->content,
            'sort_order' => (int) $material->sort_order,
            'is_completed' => $isCompleted,
            'can_download' => $material->file_path !== null,
        ];
    }

    public static function lesson(Lesson $lesson, array $completedMaterialIds = []): array
    {
        $lesson->loadMissing('materials');

        return [
            'id' => $lesson->id,
            'course_id' => $lesson->course_id,
            'title' => $lesson->title,
            'description' => $lesson->description,
            'meeting_number' => (int) $lesson->meeting_number,
            'sort_order' => (int) $lesson->sort_order,
            'materials' => $lesson->materials
                ->where('is_published', true)
                ->values()
                ->map(fn (Material $m) => self::material($m, in_array($m->id, $completedMaterialIds, true)))
                ->all(),
        ];
    }

    public static function submission(Submission $sub): array
    {
        $sub->loadMissing('student:id,name');

        return [
            'id' => $sub->id,
            'assignment_id' => $sub->assignment_id,
            'student_id' => $sub->student_id,
            'student_name' => $sub->student?->name,
            'text_answer' => $sub->text_answer,
            'file_name' => $sub->file_name,
            'file_url' => $sub->file_path ? url("/api/submissions/{$sub->id}/download") : null,
            'file_size' => $sub->file_size,
            'status' => $sub->status,
            'score' => $sub->score !== null ? (float) $sub->score : null,
            'feedback' => $sub->feedback,
            'submitted_at' => $sub->submitted_at?->toIso8601String(),
            'graded_at' => $sub->graded_at?->toIso8601String(),
        ];
    }

    public static function assignment(Assignment $assignment, ?Submission $mySubmission = null): array
    {
        $assignment->loadMissing('course:id,title');
        $status = $mySubmission?->status ?? 'upcoming';

        return [
            'id' => $assignment->id,
            'course_id' => $assignment->course_id,
            'course_title' => $assignment->course?->title ?? '',
            'title' => $assignment->title,
            'description' => $assignment->description,
            'due_at' => $assignment->due_at?->toIso8601String(),
            'max_score' => (float) $assignment->max_score,
            'submission_type' => $assignment->submission_type,
            'allow_late_submission' => (bool) $assignment->allow_late_submission,
            'attachment_name' => $assignment->attachment_name,
            'attachment_url' => $assignment->attachment_path ? url("/api/assignments/{$assignment->id}/attachment") : null,
            'status' => $status,
            'my_submission' => $mySubmission ? self::submission($mySubmission) : null,
            'submissions_count' => (int) ($assignment->submissions_count ?? 0),
        ];
    }

    public static function quiz(Quiz $quiz, int $attemptsUsed = 0): array
    {
        $quiz->loadMissing('course:id,title');

        return [
            'id' => $quiz->id,
            'course_id' => $quiz->course_id,
            'course_title' => $quiz->course?->title ?? '',
            'title' => $quiz->title,
            'description' => $quiz->description,
            'type' => $quiz->type,
            'duration_minutes' => (int) $quiz->duration_minutes,
            'starts_at' => $quiz->starts_at?->toIso8601String(),
            'ends_at' => $quiz->ends_at?->toIso8601String(),
            'max_attempts' => (int) $quiz->max_attempts,
            'passing_score' => (float) $quiz->passing_score,
            'shuffle_questions' => (bool) $quiz->shuffle_questions,
            'questions_count' => (int) ($quiz->questions_count ?? $quiz->questions()->count()),
            'attempts_used' => $attemptsUsed,
            'is_published' => (bool) $quiz->is_published,
        ];
    }

    public static function question(Question $question, bool $includeSolutions = false, ?array $optionOrder = null): array
    {
        $question->loadMissing('options');
        $options = $question->options;
        if ($optionOrder) {
            $orderMap = array_flip($optionOrder);
            $options = $options->sortBy(fn ($o) => $orderMap[$o->id] ?? $o->sort_order)->values();
        }

        return [
            'id' => $question->id,
            'type' => $question->type,
            'question_text' => $question->question_text,
            'points' => (float) ($question->pivot?->points ?? $question->points),
            'difficulty' => $question->difficulty,
            'explanation' => $includeSolutions ? $question->explanation : null,
            'options' => $options->map(fn ($o) => [
                'id' => $o->id,
                'option_text' => $o->option_text,
                'sort_order' => (int) $o->sort_order,
                'is_correct' => $includeSolutions ? (bool) $o->is_correct : null,
            ])->values()->all(),
        ];
    }

    public static function quizAttempt(QuizAttempt $attempt, bool $includeSolutions = false, bool $includeQuestions = true): array
    {
        $attempt->loadMissing(['quiz.questions.options', 'answers']);
        $questions = $attempt->quiz->questions;
        $order = $attempt->question_order ?? [];
        if ($order) {
            $orderMap = array_flip($order);
            $questions = $questions->sortBy(fn ($q) => $orderMap[$q->id] ?? 9999)->values();
        }
        $optionOrders = $attempt->option_orders ?? [];

        return [
            'id' => $attempt->id,
            'quiz_id' => $attempt->quiz_id,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'expires_at' => $attempt->expires_at?->toIso8601String(),
            'server_now' => now()->toIso8601String(),
            'status' => $attempt->status,
            'objective_score' => (float) $attempt->objective_score,
            'essay_score' => (float) $attempt->essay_score,
            'total_score' => (float) $attempt->total_score,
            'percentage' => (float) $attempt->percentage,
            'is_passed' => (bool) $attempt->is_passed,
            'needs_manual_grading' => (bool) $attempt->needs_manual_grading,
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'questions' => $includeQuestions
                ? $questions->map(
                    fn (Question $q) => self::question($q, $includeSolutions, $optionOrders[$q->id] ?? null)
                )->all()
                : [],
            'answers' => $includeQuestions
                ? $attempt->answers->map(fn ($a) => [
                    'question_id' => $a->question_id,
                    'selected_option_id' => $a->selected_option_id,
                    'essay_answer' => $a->essay_answer,
                    'score_awarded' => $a->score_awarded !== null ? (float) $a->score_awarded : null,
                    'feedback' => $a->feedback,
                ])->values()->all()
                : [],
        ];
    }

    public static function grade(Grade $grade): array
    {
        $grade->loadMissing(['course:id,title', 'student:id,name']);

        return [
            'id' => $grade->id,
            'course_id' => $grade->course_id,
            'course_title' => $grade->course?->title ?? '',
            'student_id' => $grade->student_id,
            'student_name' => $grade->student?->name,
            'assignment_avg' => (float) $grade->assignment_avg,
            'quiz_avg' => (float) $grade->quiz_avg,
            'exam_avg' => (float) $grade->exam_avg,
            'final_score' => (float) $grade->final_score,
            'letter_grade' => $grade->letter_grade,
            'instructor_feedback' => $grade->instructor_feedback,
            'updated_at' => $grade->updated_at?->toIso8601String(),
        ];
    }

    public static function certificate(Certificate $cert): array
    {
        $cert->loadMissing(['course:id,title,instructor_id', 'course.instructor:id,name', 'student:id,name']);

        return [
            'id' => $cert->id,
            'course_id' => $cert->course_id,
            'course_title' => $cert->course?->title ?? '',
            'student_name' => $cert->student?->name ?? '',
            'certificate_number' => $cert->certificate_number,
            'verification_hash' => $cert->verification_hash,
            'final_score' => (float) $cert->final_score,
            'issued_at' => $cert->issued_at?->toIso8601String(),
            'instructor_name' => $cert->course?->instructor?->name ?? '',
            'download_url' => url("/api/certificates/{$cert->id}/download"),
        ];
    }

    public static function announcement(Announcement $ann): array
    {
        $ann->loadMissing(['author:id,name', 'course:id,title']);

        return [
            'id' => $ann->id,
            'title' => $ann->title,
            'content' => $ann->content,
            'course_id' => $ann->course_id,
            'course_title' => $ann->course?->title,
            'author_name' => $ann->author?->name ?? '',
            'is_pinned' => (bool) $ann->is_pinned,
            'published_at' => $ann->published_at?->toIso8601String(),
        ];
    }

    public static function comment(Comment $comment): array
    {
        $comment->loadMissing('user:id,name');

        return [
            'id' => $comment->id,
            'discussion_id' => $comment->discussion_id,
            'author_name' => $comment->user?->name ?? '',
            'body' => $comment->body,
            'parent_id' => $comment->parent_id,
            'created_at' => $comment->created_at?->toIso8601String(),
        ];
    }

    public static function discussion(Discussion $discussion, bool $withComments = false): array
    {
        $discussion->loadMissing('user:id,name');
        $comments = $withComments
            ? $discussion->allComments()->with('user:id,name')->orderBy('created_at')->get()
            : collect();

        return [
            'id' => $discussion->id,
            'course_id' => $discussion->course_id,
            'title' => $discussion->title,
            'body' => $discussion->body,
            'author_name' => $discussion->user?->name ?? '',
            'is_pinned' => (bool) $discussion->is_pinned,
            'is_locked' => (bool) $discussion->is_locked,
            'comments_count' => (int) ($discussion->all_comments_count ?? $discussion->allComments()->count()),
            'comments' => $comments->map(fn (Comment $c) => self::comment($c))->all(),
            'created_at' => $discussion->created_at?->toIso8601String(),
        ];
    }

    public static function notification(DatabaseNotification $n): array
    {
        $data = is_array($n->data) ? $n->data : [];

        return [
            'id' => $n->id,
            'type' => $data['category'] ?? $data['type'] ?? 'announcement',
            'title' => $data['title'] ?? 'Notifikasi',
            'message' => $data['message'] ?? $data['body'] ?? '',
            'route' => $data['url'] ?? $data['route'] ?? null,
            'course_id' => $data['course_id'] ?? null,
            'is_read' => $n->read_at !== null,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
        ];
    }
}
