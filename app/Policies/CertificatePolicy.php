<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function view(User $user, Certificate $certificate): bool
    {
        return $user->isAdmin()
            || (int) $certificate->student_id === (int) $user->id
            || ($user->isInstructor() && (int) $certificate->course?->instructor_id === (int) $user->id);
    }
}
