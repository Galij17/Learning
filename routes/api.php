<?php

use App\Http\Controllers\Api\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\Instructor\AssignmentController as InstructorAssignmentController;
use App\Http\Controllers\Api\Instructor\CourseController as InstructorCourseController;
use App\Http\Controllers\Api\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\Api\Instructor\GradebookController as InstructorGradebookController;
use App\Http\Controllers\Api\Instructor\LessonMaterialController as InstructorLessonMaterialController;
use App\Http\Controllers\Api\Instructor\QuestionBankController as InstructorQuestionBankController;
use App\Http\Controllers\Api\Instructor\QuizController as InstructorQuizController;
use App\Http\Controllers\Api\SharedApiController;
use App\Http\Controllers\Api\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Api\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Api\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Api\Student\GradeController as StudentGradeController;
use App\Http\Controllers\Api\Student\QuizController as StudentQuizController;
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
        Route::get('/dashboard', [StudentDashboardController::class, 'dashboard']);
        Route::get('/courses', [StudentCourseController::class, 'courses']);
        Route::post('/courses/join', [StudentCourseController::class, 'joinCourse']);
        Route::get('/courses/{course}', [StudentCourseController::class, 'courseDetail']);
        Route::post('/courses/{course}/materials/{material}/complete', [StudentCourseController::class, 'completeMaterial']);

        Route::get('/assignments', [StudentAssignmentController::class, 'assignments']);
        Route::get('/courses/{course}/assignments/{assignment}', [StudentAssignmentController::class, 'assignmentDetail']);
        Route::post('/courses/{course}/assignments/{assignment}/submit', [StudentAssignmentController::class, 'submitAssignment'])
            ->middleware('throttle:uploads');

        Route::get('/courses/{course}/quizzes/{quiz}', [StudentQuizController::class, 'quizDetail']);
        Route::post('/courses/{course}/quizzes/{quiz}/start', [StudentQuizController::class, 'startQuiz']);
        Route::post('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/autosave', [StudentQuizController::class, 'autosaveQuiz'])
            ->middleware('throttle:exam-autosave');
        Route::post('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/submit', [StudentQuizController::class, 'submitQuiz']);
        Route::get('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/result', [StudentQuizController::class, 'quizResult']);

        Route::get('/grades', [StudentGradeController::class, 'grades']);
        Route::get('/gamification', [StudentGradeController::class, 'gamification']);
        Route::get('/leaderboard', [StudentGradeController::class, 'leaderboard']);
    });

    // Instructor / Teacher Module
    Route::prefix('instructor')->middleware('role:instructor,admin')->group(function () {
        Route::get('/dashboard', [InstructorDashboardController::class, 'dashboard']);
        Route::get('/courses', [InstructorCourseController::class, 'courses']);
        Route::post('/courses', [InstructorCourseController::class, 'storeCourse']);
        Route::get('/courses/{course}', [InstructorCourseController::class, 'showCourse']);
        Route::post('/courses/{course}/regenerate-code', [InstructorCourseController::class, 'regenerateCode']);

        Route::post('/courses/{course}/lessons', [InstructorLessonMaterialController::class, 'storeLesson']);
        Route::post('/courses/{course}/materials', [InstructorLessonMaterialController::class, 'storeMaterial'])
            ->middleware('throttle:uploads');
        Route::delete('/courses/{course}/materials/{material}', [InstructorLessonMaterialController::class, 'destroyMaterial']);

        Route::post('/courses/{course}/assignments', [InstructorAssignmentController::class, 'storeAssignment']);
        Route::get('/submissions', [InstructorAssignmentController::class, 'submissions']);
        Route::post('/submissions/{submission}/grade', [InstructorAssignmentController::class, 'gradeSubmission']);

        Route::get('/question-bank', [InstructorQuestionBankController::class, 'questionBank']);
        Route::post('/question-bank', [InstructorQuestionBankController::class, 'storeQuestion']);
        Route::post('/courses/{course}/quizzes', [InstructorQuizController::class, 'storeQuiz']);

        Route::get('/courses/{course}/gradebook', [InstructorGradebookController::class, 'gradebook']);
        Route::post('/courses/{course}/gradebook/{studentId}', [InstructorGradebookController::class, 'updateGradeFeedback']);
        Route::post('/courses/{course}/gradebook-recalculate', [InstructorGradebookController::class, 'recalculateGradebook']);
        Route::post('/courses/{course}/announcements', [InstructorCourseController::class, 'storeAnnouncement']);
    });

    // Administrator Module
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);
        Route::get('/users', [AdminUserController::class, 'users']);
        Route::post('/users', [AdminUserController::class, 'storeUser']);
        Route::put('/users/{user}', [AdminUserController::class, 'updateUser']);
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleUserStatus']);
        Route::put('/users/{user}/reset-password', [AdminUserController::class, 'resetUserPassword']);

        Route::get('/courses', [AdminCourseController::class, 'courses']);
        Route::post('/courses', [AdminCourseController::class, 'storeCourse']);
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroyCourse']);

        Route::get('/categories', [AdminCourseController::class, 'categories']);
        Route::post('/categories', [AdminCourseController::class, 'storeCategory']);

        Route::get('/reports', [AdminReportController::class, 'reports']);
        Route::get('/reports/export-pdf', [AdminReportController::class, 'exportPdf']);
        Route::get('/reports/export-excel', [AdminReportController::class, 'exportExcel']);

        Route::get('/announcements', [AdminAnnouncementController::class, 'announcements']);
        Route::post('/announcements', [AdminAnnouncementController::class, 'storeAnnouncement']);
        Route::get('/activity-logs', [AdminDashboardController::class, 'activityLogs']);
    });
});
