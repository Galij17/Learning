<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuizPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Quiz $quiz) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->quiz->type === 'exam' ? 'Ujian' : 'Kuis';

        return [
            'category' => 'quiz',
            'title' => "{$label} Baru: {$this->quiz->title}",
            'message' => "Durasi {$this->quiz->duration_minutes} menit • Nilai lulus {$this->quiz->passing_score}%",
            'url' => route('student.courses.show', $this->quiz->course_id),
            'course_id' => $this->quiz->course_id,
        ];
    }
}
