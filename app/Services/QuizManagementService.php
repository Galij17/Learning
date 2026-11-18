<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuizPublishedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class QuizManagementService
{
    public function __construct(
        private readonly QuestionManagementService $questionService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function createQuiz(Course $course, User $creator, array $data): Quiz
    {
        $quiz = DB::transaction(function () use ($course, $creator, $data) {
            $quiz = Quiz::query()->create([
                'course_id' => $course->id,
                'lesson_id' => $data['lesson_id'] ?? null,
                'created_by' => $creator->id,
                'title' => trim((string) $data['title']),
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'duration_minutes' => (int) $data['duration_minutes'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'max_attempts' => (int) ($data['max_attempts'] ?? 1),
                'passing_score' => (float) $data['passing_score'],
                'shuffle_questions' => (bool) ($data['shuffle_questions'] ?? false),
                'shuffle_options' => (bool) ($data['shuffle_options'] ?? false),
                'show_results' => (bool) ($data['show_results'] ?? true),
                'show_explanations' => (bool) ($data['show_explanations'] ?? true),
                'is_published' => (bool) ($data['is_published'] ?? false),
            ]);

            $sortOrder = 0;
            $questionIds = $data['question_ids'] ?? [];
            if ($questionIds !== []) {
                $bankQuestions = Question::query()->whereIn('id', $questionIds)->get()->keyBy('id');
                $syncData = [];
                foreach ($questionIds as $qId) {
                    if ($q = $bankQuestions->get($qId)) {
                        $sortOrder++;
                        $syncData[$qId] = ['sort_order' => $sortOrder, 'points' => $q->points];
                    }
                }
                if ($syncData !== []) {
                    $quiz->questions()->syncWithoutDetaching($syncData);
                }
            }

            foreach ($data['new_questions'] ?? [] as $item) {
                $question = $this->questionService->createQuestion($creator, [
                    'category_id' => $course->category_id,
                    'subject_id' => $course->subject_id,
                    'course_id' => $course->id,
                    'type' => $item['type'],
                    'question_text' => $item['question_text'],
                    'explanation' => $item['explanation'] ?? null,
                    'points' => $item['points'],
                    'difficulty' => $item['difficulty'] ?? 'medium',
                    'is_bank' => true,
                    'options' => $item['options'] ?? [],
                ]);

                $sortOrder++;
                $quiz->questions()->attach($question->id, [
                    'sort_order' => $sortOrder,
                    'points' => $question->points,
                ]);
            }

            return $quiz;
        });

        if ($quiz->is_published) {
            Notification::send($course->students, new QuizPublishedNotification($quiz));
        }

        $this->activityLogService->log('instructor.quiz_created', "Membuat {$quiz->type} {$quiz->title}.", $quiz);

        return $quiz;
    }

    public function updateQuiz(Course $course, Quiz $quiz, array $data): Quiz
    {
        $wasPublished = $quiz->is_published;

        $quiz->update([
            'lesson_id' => $data['lesson_id'] ?? null,
            'title' => trim((string) $data['title']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'duration_minutes' => (int) $data['duration_minutes'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'max_attempts' => (int) ($data['max_attempts'] ?? $quiz->max_attempts),
            'passing_score' => (float) $data['passing_score'],
            'shuffle_questions' => (bool) ($data['shuffle_questions'] ?? false),
            'shuffle_options' => (bool) ($data['shuffle_options'] ?? false),
            'show_results' => (bool) ($data['show_results'] ?? true),
            'show_explanations' => (bool) ($data['show_explanations'] ?? true),
            'is_published' => (bool) ($data['is_published'] ?? false),
        ]);

        if (! $wasPublished && $quiz->is_published) {
            Notification::send($course->students, new QuizPublishedNotification($quiz));
        }

        return $quiz;
    }

    /**
     * @return Collection<int, Question>
     */
    public function autoPickQuestions(Quiz $quiz, User $actor, array $filters): Collection
    {
        $attachedIds = $quiz->questions()->pluck('questions.id');

        $picked = Question::query()
            ->when(! $actor->isAdmin(), fn ($q) => $q->where('created_by', $actor->id))
            ->whereNotIn('id', $attachedIds)
            ->when($filters['subject_id'] ?? null, fn ($q, $sId) => $q->where('subject_id', $sId))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['difficulty'] ?? null, fn ($q, $diff) => $q->where('difficulty', $diff))
            ->inRandomOrder()
            ->limit((int) $filters['count'])
            ->get();

        $this->questionService->attachQuestionsToQuiz($quiz, $picked);

        return $picked;
    }

    /**
     * @return Collection<int, Question>
     */
    public function cloneFromQuiz(Quiz $targetQuiz, Quiz $sourceQuiz): Collection
    {
        $sourceQuiz->loadMissing('questions');
        $existingIds = $targetQuiz->questions()->pluck('questions.id')->all();
        $toAttach = $sourceQuiz->questions->reject(fn (Question $q) => in_array($q->id, $existingIds, true));

        $this->questionService->attachQuestionsToQuiz($targetQuiz, $toAttach);

        return $toAttach;
    }

    public function reorderQuestions(Quiz $quiz, array $questionIds): void
    {
        $attachedIds = $quiz->questions()->pluck('questions.id')->all();
        $orderedIds = array_values(array_intersect($questionIds, $attachedIds));

        DB::transaction(function () use ($quiz, $orderedIds) {
            foreach ($orderedIds as $idx => $qId) {
                $quiz->questions()->updateExistingPivot($qId, ['sort_order' => $idx + 1]);
            }
        });
    }
}
