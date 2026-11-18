<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\MaterialProgress;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GradeController extends Controller
{
    public function grades(Request $request): JsonResponse
    {
        $items = Grade::query()
            ->whereHas('course')
            ->where('student_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Grade $g) => (new GradeResource($g))->resolve())
            ->values();

        return response()->json(['data' => $items]);
    }

    public function gamification(Request $request): JsonResponse
    {
        $student = $request->user();
        $completedLessons = MaterialProgress::query()
            ->where('student_id', $student->id)
            ->where('is_completed', true)
            ->count();
        $submittedAssignments = Submission::query()
            ->where('student_id', $student->id)
            ->count();
        $completedCourses = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'completed')
            ->count();
        $hasPerfectScore = Submission::query()
            ->where('student_id', $student->id)
            ->where('score', '>=', 100)
            ->exists();

        $points = ($completedLessons * 25) + ($submittedAssignments * 50) + ($completedCourses * 200);

        $recentDates = MaterialProgress::query()
            ->where('student_id', $student->id)
            ->where('completed_at', '>=', now()->subDays(30))
            ->pluck('completed_at')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();

        return response()->json([
            'data' => [
                'points' => $points,
                'streak_days' => $recentDates,
                'completed_courses' => $completedCourses,
                'badges' => [
                    [
                        'code' => 'first_lesson',
                        'title' => 'First Lesson',
                        'description' => 'Menyelesaikan materi pembelajaran pertama.',
                        'unlocked' => $completedLessons >= 1,
                    ],
                    [
                        'code' => 'fast_learner',
                        'title' => 'Fast Learner',
                        'description' => 'Menyelesaikan minimal 3 materi.',
                        'unlocked' => $completedLessons >= 3,
                    ],
                    [
                        'code' => 'perfect_score',
                        'title' => 'Perfect Score',
                        'description' => 'Meraih nilai 100 pada tugas.',
                        'unlocked' => $hasPerfectScore,
                    ],
                    [
                        'code' => 'assignment_master',
                        'title' => 'Assignment Master',
                        'description' => 'Mengumpulkan minimal 5 tugas.',
                        'unlocked' => $submittedAssignments >= 5,
                    ],
                    [
                        'code' => 'learning_champion',
                        'title' => 'Learning Champion',
                        'description' => 'Menyelesaikan minimal 1 kelas penuh.',
                        'unlocked' => $completedCourses >= 1,
                    ],
                ],
            ],
        ]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $currentId = $request->user()->id;

        $studentUsers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_STUDENT))
            ->where('is_active', true)
            ->get(['id', 'name']);

        $studentIds = $studentUsers->pluck('id');

        $matsByStudent = MaterialProgress::query()
            ->whereIn('student_id', $studentIds)
            ->where('is_completed', true)
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $subsByStudent = Submission::query()
            ->whereIn('student_id', $studentIds)
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $doneByStudent = Enrollment::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('course')
            ->where('status', 'completed')
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $students = $studentUsers
            ->map(function (User $u) use ($currentId, $matsByStudent, $subsByStudent, $doneByStudent) {
                $mats = (int) ($matsByStudent[$u->id] ?? 0);
                $subs = (int) ($subsByStudent[$u->id] ?? 0);
                $done = (int) ($doneByStudent[$u->id] ?? 0);

                return [
                    'student_name' => $u->name,
                    'points' => ($mats * 25) + ($subs * 50) + ($done * 200),
                    'is_current_user' => $u->id === $currentId,
                ];
            })
            ->sortByDesc('points')
            ->values()
            ->take(20)
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;

                return $row;
            });

        return response()->json(['data' => $students]);
    }
}
