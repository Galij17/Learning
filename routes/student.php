<?php

use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\GradeController as StudentGradeController;
use App\Http\Controllers\Student\MaterialController as StudentMaterialController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use Illuminate\Support\Facades\Route;

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
