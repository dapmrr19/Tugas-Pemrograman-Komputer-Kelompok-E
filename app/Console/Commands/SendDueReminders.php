<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderDueNotification;

class SendDueReminders extends Command
{
    protected $signature = 'reminders:send-due';
    protected $description = 'Send due reminders as in-app (database) notifications';

    public function handle(): int
    {
        $now = now();

        $due = Reminder::where('is_sent', false)->where('remind_at', '<=', $now)->get();

        if ($due->isEmpty()) {
            $this->info('No due reminders.');
            return 0;
        }

        foreach ($due as $reminder) {
            // determine recipient: prefer user_id, else first user
            $user = null;
            if ($reminder->user_id) {
                $user = User::find($reminder->user_id);
            }
            if (!$user) {
                $user = User::first();
            }

            if ($user) {
                $user->notify(new ReminderDueNotification($reminder));
            }

            $reminder->is_sent = true;
            $reminder->save();
        }

        $this->info('Sent ' . $due->count() . ' reminders.');

        return 0;
    }
}
