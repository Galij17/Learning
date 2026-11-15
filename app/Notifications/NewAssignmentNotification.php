<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Assignment $assignment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'assignment',
            'title' => 'Tugas Baru: ' . $this->assignment->title,
            'message' => 'Batas pengumpulan: ' . ($this->assignment->due_at?->format('d M Y H:i') ?? 'Tidak dibatasi'),
            'url' => route('student.courses.show', $this->assignment->course_id),
            'course_id' => $this->assignment->course_id,
        ];
    }
}
