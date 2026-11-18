<?php

use App\Http\Controllers\AssignmentAttachmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\MaterialAccessController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
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

    require __DIR__ . '/admin.php';
    require __DIR__ . '/instructor.php';
    require __DIR__ . '/student.php';
});
