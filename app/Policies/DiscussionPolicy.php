<?php

namespace App\Policies;

use App\Models\Discussion;
use App\Models\User;

class DiscussionPolicy
{
    public function view(User $user, Discussion $discussion): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor() && (int) $discussion->course?->instructor_id === (int) $user->id) {
            return true;
        }

        return $discussion->course
            ?->enrollments()
            ->where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists() ?? false;
    }

    public function moderate(User $user, Discussion $discussion): bool
    {
        return $user->isAdmin()
            || ($user->isInstructor() && (int) $discussion->course?->instructor_id === (int) $user->id);
    }

    public function delete(User $user, Discussion $discussion): bool
    {
        return $this->moderate($user, $discussion) || (int) $discussion->user_id === (int) $user->id;
    }
}
