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
        if (! $assignment->course) {
            continue;
        }
        $submittedStudentIds = $assignment->submissions->pluck('student_id')->all();
        $activeStudents = $assignment->course->students()
            ->wherePivot('status', '!=', 'dropped')
            ->get();

        foreach ($activeStudents as $student) {
            if (in_array($student->id, $submittedStudentIds, true)) {
                continue;
            }
            $expectedUrl = route('student.courses.show', $assignment->course_id);
            $alreadyNotified = $student->notifications()
                ->where('type', AssignmentDeadlineReminderNotification::class)
                ->where('created_at', '>=', now()->subHours(24))
                ->get(['data'])
                ->contains(function ($n) use ($assignment, $expectedUrl) {
                    $data = is_array($n->data) ? $n->data : [];

                    return ($data['assignment_id'] ?? null) === $assignment->id
                        || (($data['title'] ?? '') === 'Pengingat Deadline: ' . $assignment->title && ($data['url'] ?? '') === $expectedUrl);
                });

            if (! $alreadyNotified) {
                $student->notify(new AssignmentDeadlineReminderNotification($assignment));
                $sentCount++;
            }
        }
    }

    $this->info("Pengingat deadline terkirim: {$sentCount} notifikasi.");
})->purpose('Kirim notifikasi pengingat deadline tugas dalam 24 jam ke depan');

Schedule::command('lms:send-deadline-reminders')->hourly();
