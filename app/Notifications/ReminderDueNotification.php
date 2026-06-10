<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ReminderDueNotification extends Notification
{
    use Queueable;

    protected $reminder;

    public function __construct($reminder)
    {
        $this->reminder = $reminder;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $now = Carbon::now();

        $taskName = $this->reminder->title ?? null;

        // prefer task deadline, fallback to reminder remind_at
        $target = null;
        if ($this->reminder->task && $this->reminder->task->deadline) {
            $target = $this->reminder->task->deadline;
        } elseif ($this->reminder->remind_at) {
            $target = $this->reminder->remind_at;
        }

        $countdown = null;
        if ($target) {
            $diff = $target->diffInSeconds($now, false);
            $abs = abs($diff);
            $days = intdiv($abs, 86400);
            $hours = intdiv($abs % 86400, 3600);
            $minutes = intdiv($abs % 3600, 60);
            $str = sprintf('%sd %sh %sm', $days, $hours, $minutes);
            if ($diff < 0) {
                $countdown = 'Overdue ' . $str;
            } else {
                $countdown = $str;
            }
        }

        return [
            'reminder_id' => $this->reminder->id,
            'title' => $this->reminder->title,
            'note' => $this->reminder->note,
            'task_id' => $this->reminder->task_id,
            'remind_at' => $this->reminder->remind_at->toIso8601String(),
            'task_name' => $taskName,
            'due_countdown' => $countdown,
        ];
    }
}
