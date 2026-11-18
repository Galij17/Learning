<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionManagementService
{
    public function createQuestion(User $creator, array $data): Question
    {
        return DB::transaction(function () use ($creator, $data) {
            $question = Question::query()->create([
                'category_id' => $data['category_id'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
                'course_id' => $data['course_id'] ?? null,
                'created_by' => $creator->id,
                'type' => $data['type'],
                'question_text' => trim((string) $data['question_text']),
                'explanation' => $data['explanation'] ?? null,
                'points' => (float) $data['points'],
                'difficulty' => $data['difficulty'] ?? 'medium',
                'is_bank' => (bool) ($data['is_bank'] ?? true),
            ]);

            if ($question->type !== 'essay') {
                foreach ($data['options'] ?? [] as $idx => $opt) {
                    QuestionOption::query()->create([
                        'question_id' => $question->id,
                        'option_text' => $opt['option_text'],
                        'is_correct' => (bool) ($opt['is_correct'] ?? false),
                        'sort_order' => $idx + 1,
                    ]);
                }
            }

            return $question;
        });
    }

    public function updateQuestion(Question $question, array $data): Question
    {
        return DB::transaction(function () use ($question, $data) {
            $question->update([
                'category_id' => $data['category_id'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
                'type' => $data['type'],
                'question_text' => trim((string) $data['question_text']),
                'explanation' => $data['explanation'] ?? null,
                'points' => (float) $data['points'],
                'difficulty' => $data['difficulty'] ?? 'medium',
            ]);

            $question->options()->delete();
            if ($question->type !== 'essay') {
                foreach ($data['options'] ?? [] as $idx => $opt) {
                    QuestionOption::query()->create([
                        'question_id' => $question->id,
                        'option_text' => $opt['option_text'],
                        'is_correct' => (bool) ($opt['is_correct'] ?? false),
                        'sort_order' => $idx + 1,
                    ]);
                }
            }

            return $question->fresh('options');
        });
    }

    public function duplicateQuestion(Question $question, User $creator): Question
    {
        $question->loadMissing('options');

        return DB::transaction(function () use ($question, $creator) {
            $copy = Question::query()->create([
                'category_id' => $question->category_id,
                'subject_id' => $question->subject_id,
                'course_id' => $question->course_id,
                'created_by' => $creator->id,
                'type' => $question->type,
                'question_text' => $question->question_text . ' (Salinan)',
                'explanation' => $question->explanation,
                'points' => $question->points,
                'difficulty' => $question->difficulty,
                'is_bank' => true,
            ]);

            foreach ($question->options as $opt) {
                QuestionOption::query()->create([
                    'question_id' => $copy->id,
                    'option_text' => $opt->option_text,
                    'is_correct' => $opt->is_correct,
                    'sort_order' => $opt->sort_order,
                ]);
            }

            return $copy;
        });
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    public function attachQuestionsToQuiz(Quiz $quiz, Collection $questions): void
    {
        if ($questions->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($quiz, $questions) {
            $currentCount = $quiz->questions()->count();
            $attachData = [];
            foreach ($questions->values() as $idx => $question) {
                $attachData[$question->id] = [
                    'sort_order' => $currentCount + $idx + 1,
                    'points' => $question->points,
                ];
            }

            $quiz->questions()->syncWithoutDetaching($attachData);
        });
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    public function bulkDelete(Collection $questions): void
    {
        Question::query()->whereIn('id', $questions->pluck('id'))->delete();
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    public function bulkMoveToSubject(Collection $questions, Subject $subject): void
    {
        Question::query()
            ->whereIn('id', $questions->pluck('id'))
            ->update([
                'subject_id' => $subject->id,
                'category_id' => $subject->category_id,
            ]);
    }
}
