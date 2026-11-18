<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly GradingService $gradingService
    ) {}

    public function joinByCode(User $student, string $code): Enrollment
    {
        $course = Course::query()
            ->where('code', strtoupper(trim($code)))
            ->where('status', 'published')
            ->first();

        if (! $course) {
            throw ValidationException::withMessages([
                'code' => 'Kode kelas tidak ditemukan atau kelas belum dipublikasikan.',
            ]);
        }

        return DB::transaction(function () use ($course, $student) {
            $existing = Enrollment::query()
                ->where('course_id', $course->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->status === 'dropped') {
                    $existing->update(['status' => 'active']);
                    $this->activityLogService->log(
                        'course.enrolled',
                        "{$student->name} bergabung kembali ke kelas {$course->title}.",
                        $course,
                        ['course_code' => $course->code],
                        $student
                    );

                    return $existing->fresh();
                }

                throw ValidationException::withMessages([
                    'code' => 'Anda sudah terdaftar pada kelas ini.',
                ]);
            }

            $enrollment = Enrollment::query()->create([
                'course_id' => $course->id,
                'student_id' => $student->id,
                'status' => 'active',
                'progress_percentage' => 0,
                'enrolled_at' => now(),
            ]);

            $this->activityLogService->log(
                'course.enrolled',
                "{$student->name} bergabung ke kelas {$course->title}.",
                $course,
                ['course_code' => $course->code],
                $student
            );

            return $enrollment;
        });
    }

    public function markMaterialCompleted(Material $material, User $student): Enrollment
    {
        return DB::transaction(function () use ($material, $student) {
            MaterialProgress::query()->updateOrCreate(
                ['material_id' => $material->id, 'student_id' => $student->id],
                ['is_completed' => true, 'completed_at' => now()]
            );

            return $this->recalculateProgress($material->course, $student);
        });
    }

    public function recalculateProgress(Course $course, User $student): Enrollment
    {
        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->where('status', '!=', 'dropped')
            ->first();

        if (! $enrollment) {
            throw ValidationException::withMessages([
                'enrollment' => 'Anda tidak terdaftar aktif pada kelas ini.',
            ]);
        }

        $totalMaterials = Material::query()
            ->where('course_id', $course->id)
            ->where('is_published', true)
            ->whereHas('lesson', fn ($q) => $q->whereNull('deleted_at'))
            ->count();

        $completedMaterials = MaterialProgress::query()
            ->where('student_id', $student->id)
            ->where('is_completed', true)
            ->whereHas('material', fn ($q) => $q
                ->where('course_id', $course->id)
                ->where('is_published', true)
                ->whereHas('lesson', fn ($l) => $l->whereNull('deleted_at')))
            ->count();

        $progress = $totalMaterials > 0
            ? round(($completedMaterials / $totalMaterials) * 100, 2)
            : 100.0;

        $enrollment->progress_percentage = $progress;
        if ($progress >= 100 && $enrollment->status === 'active') {
            $enrollment->status = 'completed';
            $enrollment->completed_at = now();
        }
        $enrollment->save();

        $this->issueCertificateIfEligible($course, $student, $enrollment);

        return $enrollment->fresh();
    }

    public function issueCertificateIfEligible(Course $course, User $student, ?Enrollment $enrollment = null): ?Certificate
    {
        if (! $course->certificate_enabled) {
            return null;
        }

        $enrollment ??= Enrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $enrollment || $enrollment->status === 'dropped') {
            return null;
        }

        $grade = Grade::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $grade) {
            $grade = $this->gradingService->recalculateStudentGrade($course, $student);
        }

        $meetsProgress = (float) $enrollment->progress_percentage >= (float) $course->min_completion_percentage;
        $meetsScore = (float) $grade->final_score >= (float) $course->min_passing_score;

        if (! ($meetsProgress && $meetsScore)) {
            return null;
        }

        $existing = Certificate::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            if ((float) $existing->final_score !== (float) $grade->final_score) {
                $existing->update(['final_score' => $grade->final_score]);
            }

            return $existing;
        }

        return Certificate::query()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'certificate_number' => 'VLMS-' . now()->format('Y') . '-' . strtoupper(Str::random(6)),
            'final_score' => $grade->final_score,
            'verification_hash' => hash('sha256', $course->id . '-' . $student->id . '-' . Str::uuid()->toString()),
            'issued_at' => now(),
        ]);
    }
}
