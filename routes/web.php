<?php

use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseManagementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AssignmentAttachmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\Instructor\AssignmentController as InstructorAssignmentController;
use App\Http\Controllers\Instructor\CourseAnnouncementController;
use App\Http\Controllers\Instructor\CourseController as InstructorCourseController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\Instructor\GradebookController;
use App\Http\Controllers\Instructor\LessonController as InstructorLessonController;
use App\Http\Controllers\Instructor\MaterialController as InstructorMaterialController;
use App\Http\Controllers\Instructor\QuestionBankController;
use App\Http\Controllers\Instructor\QuizController as InstructorQuizController;
use App\Http\Controllers\MaterialAccessController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\GradeController as StudentGradeController;
use App\Http\Controllers\Student\MaterialController as StudentMaterialController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    if ($user = $request->user()) {
        return match ($user->primaryRole()) {
            'admin' => redirect()->route('admin.dashboard'),
            'instructor' => redirect()->route('instructor.dashboard'),
            default => redirect()->route('student.dashboard'),
        };
    }

    return Inertia::render('Welcome');
})->name('home');

Route::get('/certificates/verify/{hash}', [CertificateController::class, 'verify'])
    ->name('certificates.verify');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register')
        ->name('register.store');

    Route::get('/reset-password/{token}', fn (string $token) => redirect()->route('login')
        ->with('warning', 'Silakan hubungi Administrator untuk reset kata sandi akun Anda.'))
        ->name('password.reset');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Profile & Notifications
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Private File Access (authorized via Policies)
    Route::get('/materials/{material}/download', [MaterialAccessController::class, 'download'])->name('materials.download');
    Route::get('/materials/{material}/stream', [MaterialAccessController::class, 'stream'])->name('materials.stream');
    Route::get('/assignments/{assignment}/attachment', [AssignmentAttachmentController::class, 'downloadAssignment'])->name('assignments.attachment');
    Route::get('/submissions/{submission}/download', [AssignmentAttachmentController::class, 'downloadSubmission'])->name('submissions.download');

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Course Discussions (shared across enrolled students, course instructor, and admin)
    Route::prefix('courses/{course}/discussions')->name('courses.discussions.')->group(function () {
        Route::get('/', [DiscussionController::class, 'index'])->name('index');
        Route::post('/', [DiscussionController::class, 'store'])->name('store');
        Route::get('/{discussion}', [DiscussionController::class, 'show'])->name('show');
        Route::post('/{discussion}/comments', [DiscussionController::class, 'storeComment'])->name('comments.store');
        Route::post('/{discussion}/pin', [DiscussionController::class, 'togglePin'])->name('pin');
        Route::post('/{discussion}/lock', [DiscussionController::class, 'toggleLock'])->name('lock');
        Route::delete('/{discussion}', [DiscussionController::class, 'destroy'])->name('destroy');
        Route::delete('/{discussion}/comments/{comment}', [DiscussionController::class, 'destroyComment'])->name('comments.destroy');
    });

    // =========================================================
    // ADMIN MODULE
    // =========================================================
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::put('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::get('/periods', [AcademicPeriodController::class, 'index'])->name('periods.index');
        Route::post('/periods', [AcademicPeriodController::class, 'store'])->name('periods.store');
        Route::put('/periods/{period}', [AcademicPeriodController::class, 'update'])->name('periods.update');
        Route::delete('/periods/{period}', [AcademicPeriodController::class, 'destroy'])->name('periods.destroy');

        Route::get('/courses', [CourseManagementController::class, 'index'])->name('courses.index');
        Route::post('/courses', [CourseManagementController::class, 'store'])->name('courses.store');
        Route::put('/courses/{course}', [CourseManagementController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [CourseManagementController::class, 'destroy'])->name('courses.destroy');

        Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [AdminReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/reports/export-excel', [AdminReportController::class, 'exportExcel'])->name('reports.export-excel');

        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    });

    // =========================================================
    // INSTRUCTOR MODULE
    // =========================================================
    Route::prefix('instructor')->name('instructor.')->middleware('role:instructor,admin')->group(function () {
        Route::get('/dashboard', [InstructorDashboardController::class, 'index'])->name('dashboard');

        Route::get('/courses', [InstructorCourseController::class, 'index'])->name('courses.index');
        Route::post('/courses', [InstructorCourseController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}', [InstructorCourseController::class, 'show'])->name('courses.show');
        Route::post('/courses/{course}', [InstructorCourseController::class, 'update'])->name('courses.update');
        Route::post('/courses/{course}/regenerate-code', [InstructorCourseController::class, 'regenerateCode'])->name('courses.regenerate-code');
        Route::post('/courses/{course}/students', [InstructorCourseController::class, 'enrollStudent'])->name('courses.students.enroll');
        Route::delete('/courses/{course}/students/{student}', [InstructorCourseController::class, 'removeStudent'])->name('courses.students.remove');
        Route::delete('/courses/{course}', [InstructorCourseController::class, 'destroy'])->name('courses.destroy');

        // Lessons & Materials
        Route::post('/courses/{course}/lessons', [InstructorLessonController::class, 'store'])->name('lessons.store');
        Route::put('/courses/{course}/lessons/{lesson}', [InstructorLessonController::class, 'update'])->name('lessons.update');
        Route::delete('/courses/{course}/lessons/{lesson}', [InstructorLessonController::class, 'destroy'])->name('lessons.destroy');

        Route::post('/courses/{course}/materials', [InstructorMaterialController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('materials.store');
        Route::post('/courses/{course}/materials/{material}', [InstructorMaterialController::class, 'update'])
            ->middleware('throttle:uploads')
            ->name('materials.update');
        Route::delete('/courses/{course}/materials/{material}', [InstructorMaterialController::class, 'destroy'])->name('materials.destroy');

        // Assignments & Grading
        Route::post('/courses/{course}/assignments', [InstructorAssignmentController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('assignments.store');
        Route::get('/courses/{course}/assignments/{assignment}', [InstructorAssignmentController::class, 'show'])->name('assignments.show');
        Route::post('/courses/{course}/assignments/{assignment}', [InstructorAssignmentController::class, 'update'])
            ->middleware('throttle:uploads')
            ->name('assignments.update');
        Route::delete('/courses/{course}/assignments/{assignment}', [InstructorAssignmentController::class, 'destroy'])->name('assignments.destroy');
        Route::post('/submissions/{submission}/grade', [InstructorAssignmentController::class, 'gradeSubmission'])->name('submissions.grade');

        // Question Bank
        Route::get('/question-bank', [QuestionBankController::class, 'index'])->name('question-bank.index');
        Route::post('/question-bank', [QuestionBankController::class, 'store'])->name('question-bank.store');
        Route::put('/question-bank/{question}', [QuestionBankController::class, 'update'])->name('question-bank.update');
        Route::delete('/question-bank/{question}', [QuestionBankController::class, 'destroy'])->name('question-bank.destroy');

        // Quizzes & Exams
        Route::post('/courses/{course}/quizzes', [InstructorQuizController::class, 'store'])->name('quizzes.store');
        Route::get('/courses/{course}/quizzes/{quiz}', [InstructorQuizController::class, 'show'])->name('quizzes.show');
        Route::put('/courses/{course}/quizzes/{quiz}', [InstructorQuizController::class, 'update'])->name('quizzes.update');
        Route::post('/courses/{course}/quizzes/{quiz}/attach-questions', [InstructorQuizController::class, 'attachQuestions'])->name('quizzes.attach-questions');
        Route::post('/courses/{course}/quizzes/{quiz}/inline-question', [InstructorQuizController::class, 'createInlineQuestion'])->name('quizzes.inline-question');
        Route::delete('/courses/{course}/quizzes/{quiz}/questions/{question}', [InstructorQuizController::class, 'detachQuestion'])->name('quizzes.detach-question');
        Route::post('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/grade-essays', [InstructorQuizController::class, 'gradeEssays'])->name('quizzes.grade-essays');
        Route::delete('/courses/{course}/quizzes/{quiz}', [InstructorQuizController::class, 'destroy'])->name('quizzes.destroy');

        // Gradebook & Course Announcements
        Route::get('/courses/{course}/gradebook', [GradebookController::class, 'index'])->name('gradebook.index');
        Route::post('/courses/{course}/gradebook/{student}', [GradebookController::class, 'updateFeedback'])->name('gradebook.update-feedback');
        Route::post('/courses/{course}/gradebook-recalculate', [GradebookController::class, 'recalculateAll'])->name('gradebook.recalculate');

        Route::post('/courses/{course}/announcements', [CourseAnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/courses/{course}/announcements/{announcement}', [CourseAnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    // =========================================================
    // STUDENT MODULE
    // =========================================================
    Route::prefix('student')->name('student.')->middleware('role:student,admin')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses.index');
        Route::post('/courses/join', [StudentCourseController::class, 'join'])->name('courses.join');
        Route::get('/courses/{course}', [StudentCourseController::class, 'show'])->name('courses.show');

        Route::get('/courses/{course}/materials/{material}', [StudentMaterialController::class, 'show'])->name('materials.show');
        Route::post('/courses/{course}/materials/{material}/complete', [StudentMaterialController::class, 'complete'])->name('materials.complete');

        Route::get('/courses/{course}/assignments/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignments.show');
        Route::post('/courses/{course}/assignments/{assignment}/submit', [StudentAssignmentController::class, 'submit'])
            ->middleware('throttle:uploads')
            ->name('assignments.submit');

        Route::get('/courses/{course}/quizzes/{quiz}', [StudentQuizController::class, 'show'])->name('quizzes.show');
        Route::post('/courses/{course}/quizzes/{quiz}/start', [StudentQuizController::class, 'start'])->name('quizzes.start');
        Route::get('/courses/{course}/quizzes/{quiz}/attempts/{attempt}', [StudentQuizController::class, 'take'])->name('quizzes.take');
        Route::post('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/autosave', [StudentQuizController::class, 'autosave'])
            ->middleware('throttle:exam-autosave')
            ->name('quizzes.autosave');
        Route::post('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/submit', [StudentQuizController::class, 'submit'])->name('quizzes.submit');
        Route::get('/courses/{course}/quizzes/{quiz}/attempts/{attempt}/result', [StudentQuizController::class, 'result'])->name('quizzes.result');

        Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');
    });
});
