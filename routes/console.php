<?php

use App\Models\Assignment;
use App\Notifications\AssignmentDeadlineReminderNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lms:send-deadline-reminders', function () {
    $upcoming = Assignment::query()
        ->where('is_published', true)
        ->whereBetween('due_at', [now(), now()->addHours(24)])
        ->with(['course.students', 'submissions'])
        ->get();

    $sentCount = 0;
    foreach ($upcoming as $assignment) {
        $submittedStudentIds = $assignment->submissions->pluck('student_id')->all();
        foreach ($assignment->course->students as $student) {
            if (in_array($student->id, $submittedStudentIds, true)) {
                continue;
            }
            $alreadyNotified = $student->notifications()
                ->where('type', AssignmentDeadlineReminderNotification::class)
                ->where('data->course_id', $assignment->course_id)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if (! $alreadyNotified) {
                $student->notify(new AssignmentDeadlineReminderNotification($assignment));
                $sentCount++;
            }
        }
    }

    $this->info("Pengingat deadline terkirim: {$sentCount} notifikasi.");
})->purpose('Kirim notifikasi pengingat deadline tugas dalam 24 jam ke depan');

Schedule::command('lms:send-deadline-reminders')->hourly();
