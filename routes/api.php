<?php

use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\InstructorApiController;
use App\Http\Controllers\Api\SharedApiController;
use App\Http\Controllers\Api\StudentApiController;
use Illuminate\Support\Facades\Route;

// Public Auth & Certificate Verification
Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:login');
Route::post('/register', [AuthApiController::class, 'register'])->middleware('throttle:register');
Route::post('/forgot-password', [AuthApiController::class, 'forgotPassword'])->middleware('throttle:login');
Route::get('/certificates/verify/{hash}', [SharedApiController::class, 'certificateVerify']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/user', [AuthApiController::class, 'user']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // Shared Profile, Notifications, Certificates, Discussions, Files
    Route::post('/profile', [SharedApiController::class, 'updateProfile']);
    Route::put('/profile/password', [SharedApiController::class, 'updatePassword']);

    Route::get('/notifications', [SharedApiController::class, 'notifications']);
    Route::post('/notifications/{id}/read', [SharedApiController::class, 'markNotificationRead']);
    Route::post('/notifications/read-all', [SharedApiController::class, 'markAllNotificationsRead']);
    Route::delete('/notifications/{id}', [SharedApiController::class, 'deleteNotification']);

    Route::get('/certificates', [SharedApiController::class, 'certificates']);
    Route::get('/certificates/{certificate}', [SharedApiController::class, 'certificateShow']);
    Route::get('/certificates/{certificate}/download', [SharedApiController::class, 'certificateDownload']);

    Route::get('/courses/{course}/discussions', [SharedApiController::class, 'discussionsIndex']);
    Route::post('/courses/{course}/discussions', [SharedApiController::class, 'discussionsStore']);
    Route::get('/courses/{course}/discussions/{discussion}', [SharedApiController::class, 'discussionsShow']);
    Route::post('/courses/{course}/discussions/{discussion}/comments', [SharedApiController::class, 'discussionsComment']);

    Route::get('/materials/{material}/download', [SharedApiController::class, 'materialDownload']);
    Route::get('/materials/{material}/stream', [SharedApiController::class, 'materialStream']);
    Route::get('/assignments/{assignment}/attachment', [SharedApiController::class, 'assignmentAttachment']);
    Route::get('/submissions/{submission}/download', [SharedApiController::class, 'submissionDownload']);

    // Student Module
    Route::prefix('student')->middleware('role:student,admin')->group(function () {
        Route::get('/dashboard', [StudentApiController::class, 'dashboard']);
        Route::get('/courses', [StudentApiController::class, 'courses']);
        Route::post('/courses/join', [StudentApiController::class, 'joinCourse']);
        Route::get('/courses/{course}', [StudentApiController::class, 'courseDetail']);
        Route::post('/courses/{course}/materials/{material}/complete', [StudentApiController::class, 'completeMaterial']);

        Route::get('/assignments', [StudentApiController::class, 'assignments']);
        Route::get('/courses/{course}/assignments/{assignment}', [StudentApiController::class, 'assignmentDetail']);
        Route::post('/courses/{course}/assignments/{assignment}/submit', [StudentApiController::class, 'submitAssignment'])
            ->middleware('throttle:uploads');

        Route::get('/courses/{course}/quizzes/{quiz}', [StudentApiController::class, 'quizDetail']);
        Route::post('/courses/{course}/quizzes/{quiz}/start', [StudentApiController::class, 'startQuiz']);
        Route::post('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/autosave', [StudentApiController::class, 'autosaveQuiz'])
            ->middleware('throttle:exam-autosave');
        Route::post('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/submit', [StudentApiController::class, 'submitQuiz']);
        Route::get('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/result', [StudentApiController::class, 'quizResult']);

        Route::get('/grades', [StudentApiController::class, 'grades']);
        Route::get('/gamification', [StudentApiController::class, 'gamification']);
        Route::get('/leaderboard', [StudentApiController::class, 'leaderboard']);
    });

    // Instructor / Teacher Module
    Route::prefix('instructor')->middleware('role:instructor,admin')->group(function () {
        Route::get('/dashboard', [InstructorApiController::class, 'dashboard']);
        Route::get('/courses', [InstructorApiController::class, 'courses']);
        Route::post('/courses', [InstructorApiController::class, 'storeCourse']);
        Route::get('/courses/{course}', [InstructorApiController::class, 'showCourse']);
        Route::post('/courses/{course}/regenerate-code', [InstructorApiController::class, 'regenerateCode']);

        Route::post('/courses/{course}/lessons', [InstructorApiController::class, 'storeLesson']);
        Route::post('/courses/{course}/materials', [InstructorApiController::class, 'storeMaterial'])
            ->middleware('throttle:uploads');
        Route::delete('/courses/{course}/materials/{material}', [InstructorApiController::class, 'destroyMaterial']);

        Route::post('/courses/{course}/assignments', [InstructorApiController::class, 'storeAssignment']);
        Route::get('/submissions', [InstructorApiController::class, 'submissions']);
        Route::post('/submissions/{submission}/grade', [InstructorApiController::class, 'gradeSubmission']);

        Route::get('/question-bank', [InstructorApiController::class, 'questionBank']);
        Route::post('/question-bank', [InstructorApiController::class, 'storeQuestion']);
        Route::post('/courses/{course}/quizzes', [InstructorApiController::class, 'storeQuiz']);

        Route::get('/courses/{course}/gradebook', [InstructorApiController::class, 'gradebook']);
        Route::post('/courses/{course}/gradebook/{studentId}', [InstructorApiController::class, 'updateGradeFeedback']);
        Route::post('/courses/{course}/gradebook-recalculate', [InstructorApiController::class, 'recalculateGradebook']);
        Route::post('/courses/{course}/announcements', [InstructorApiController::class, 'storeAnnouncement']);
    });

    // Administrator Module
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminApiController::class, 'dashboard']);
        Route::get('/users', [AdminApiController::class, 'users']);
        Route::post('/users', [AdminApiController::class, 'storeUser']);
        Route::put('/users/{user}', [AdminApiController::class, 'updateUser']);
        Route::patch('/users/{user}/toggle-status', [AdminApiController::class, 'toggleUserStatus']);
        Route::put('/users/{user}/reset-password', [AdminApiController::class, 'resetUserPassword']);

        Route::get('/courses', [AdminApiController::class, 'courses']);
        Route::post('/courses', [AdminApiController::class, 'storeCourse']);
        Route::delete('/courses/{course}', [AdminApiController::class, 'destroyCourse']);

        Route::get('/categories', [AdminApiController::class, 'categories']);
        Route::post('/categories', [AdminApiController::class, 'storeCategory']);

        Route::get('/reports', [AdminApiController::class, 'reports']);
        Route::get('/reports/export-pdf', [AdminApiController::class, 'exportPdf']);
        Route::get('/reports/export-excel', [AdminApiController::class, 'exportExcel']);

        Route::get('/announcements', [AdminApiController::class, 'announcements']);
        Route::post('/announcements', [AdminApiController::class, 'storeAnnouncement']);
        Route::get('/activity-logs', [AdminApiController::class, 'activityLogs']);
    });
});
