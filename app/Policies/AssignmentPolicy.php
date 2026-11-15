<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor() && (int) $assignment->course?->instructor_id === (int) $user->id) {
            return true;
        }

        return $assignment->is_published && $assignment->course
            ?->enrollments()
            ->where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }

    public function manage(User $user, Assignment $assignment): bool
    {
        return $user->isAdmin()
            || ($user->isInstructor() && (int) $assignment->course?->instructor_id === (int) $user->id);
    }

    public function submit(User $user, Assignment $assignment): bool
    {
        if (! $assignment->is_published) {
            return false;
        }

        return $assignment->course
            ?->enrollments()
            ->where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists() ?? false;
    }
}
