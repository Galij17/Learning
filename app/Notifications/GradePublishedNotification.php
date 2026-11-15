<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GradePublishedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Course $course,
        public readonly string $itemTitle,
        public readonly float $score,
        public readonly string $url
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'grade',
            'title' => 'Nilai Dirilis: ' . $this->itemTitle,
            'message' => "{$this->course->title} — Skor Anda: " . number_format($this->score, 2),
            'url' => $this->url,
            'course_id' => $this->course->id,
        ];
    }
}
