<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function view(User $user, Quiz $quiz): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor() && (int) $quiz->course?->instructor_id === (int) $user->id) {
            return true;
        }

        return $quiz->is_published && ($quiz->course
            ?->enrollments()
            ->where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists() ?? false);
    }

    public function manage(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin()
            || ($user->isInstructor() && (int) $quiz->course?->instructor_id === (int) $user->id);
    }

    public function attempt(User $user, Quiz $quiz): bool
    {
        if (! $quiz->is_published) {
            return false;
        }

        return $quiz->course
            ?->enrollments()
            ->where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists() ?? false;
    }
}
