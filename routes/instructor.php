<?php

use App\Http\Controllers\Instructor\AssignmentController as InstructorAssignmentController;
use App\Http\Controllers\Instructor\CourseAnnouncementController;
use App\Http\Controllers\Instructor\CourseController as InstructorCourseController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\Instructor\GradebookController;
use App\Http\Controllers\Instructor\LessonController as InstructorLessonController;
use App\Http\Controllers\Instructor\MaterialController as InstructorMaterialController;
use App\Http\Controllers\Instructor\QuestionBankController;
use App\Http\Controllers\Instructor\QuizController as InstructorQuizController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/question-bank/template', [QuestionBankController::class, 'downloadTemplate'])->name('question-bank.template');
    Route::post('/question-bank/import-preview', [QuestionBankController::class, 'previewImport'])
        ->middleware('throttle:uploads')
        ->name('question-bank.import-preview');
    Route::post('/question-bank/import', [QuestionBankController::class, 'importExcel'])
        ->middleware('throttle:uploads')
        ->name('question-bank.import');
    Route::post('/question-bank/bulk-action', [QuestionBankController::class, 'bulkAction'])->name('question-bank.bulk-action');
    Route::post('/question-bank', [QuestionBankController::class, 'store'])->name('question-bank.store');
    Route::post('/question-bank/{question}/duplicate', [QuestionBankController::class, 'duplicate'])->name('question-bank.duplicate');
    Route::put('/question-bank/{question}', [QuestionBankController::class, 'update'])->name('question-bank.update');
    Route::delete('/question-bank/{question}', [QuestionBankController::class, 'destroy'])->name('question-bank.destroy');

    // Quizzes & Exams
    Route::get('/courses/{course}/quizzes/create', [InstructorQuizController::class, 'create'])->name('quizzes.create');
    Route::post('/courses/{course}/quizzes', [InstructorQuizController::class, 'store'])->name('quizzes.store');
    Route::get('/courses/{course}/quizzes/{quiz}', [InstructorQuizController::class, 'show'])->name('quizzes.show');
    Route::put('/courses/{course}/quizzes/{quiz}', [InstructorQuizController::class, 'update'])->name('quizzes.update');
    Route::post('/courses/{course}/quizzes/{quiz}/attach-questions', [InstructorQuizController::class, 'attachQuestions'])->name('quizzes.attach-questions');
    Route::post('/courses/{course}/quizzes/{quiz}/auto-pick', [InstructorQuizController::class, 'autoPickQuestions'])->name('quizzes.auto-pick');
    Route::post('/courses/{course}/quizzes/{quiz}/clone-questions', [InstructorQuizController::class, 'cloneFromQuiz'])->name('quizzes.clone-questions');
    Route::post('/courses/{course}/quizzes/{quiz}/import-questions', [InstructorQuizController::class, 'importQuestions'])
        ->middleware('throttle:uploads')
        ->name('quizzes.import-questions');
    Route::post('/courses/{course}/quizzes/{quiz}/inline-question', [InstructorQuizController::class, 'createInlineQuestion'])->name('quizzes.inline-question');
    Route::patch('/courses/{course}/quizzes/{quiz}/questions/{question}/points', [InstructorQuizController::class, 'updateQuestionPoints'])->name('quizzes.question-points');
    Route::post('/courses/{course}/quizzes/{quiz}/reorder-questions', [InstructorQuizController::class, 'reorderQuestions'])->name('quizzes.reorder-questions');
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
