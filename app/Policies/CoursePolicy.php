<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor() && (int) $course->instructor_id === (int) $user->id) {
            return true;
        }

        return $course->enrollments()
            ->where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isInstructor() && (int) $course->instructor_id === (int) $user->id);
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isInstructor() && (int) $course->instructor_id === (int) $user->id);
    }

    public function manageStudents(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }
}
