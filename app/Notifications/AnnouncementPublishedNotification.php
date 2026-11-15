<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'announcement',
            'title' => 'Pengumuman: ' . $this->announcement->title,
            'message' => str(strip_tags($this->announcement->content))->limit(100)->toString(),
            'url' => $this->announcement->course_id
                ? route('student.courses.show', $this->announcement->course_id)
                : route('notifications.index'),
            'course_id' => $this->announcement->course_id,
        ];
    }
}
