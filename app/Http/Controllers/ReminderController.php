<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;

class ReminderController extends Controller
{
    // Global reminder views removed — reminders are managed per-task via `storeForTask`.

    public function storeForTask(Request $request, Task $task)
    {
        $data = $request->validate([
            'remind_at' => 'required|date',
            'note' => 'nullable|string',
        ]);

        // Determine single user
        $user = auth()->user() ?? User::first();

        // If a reminder already exists for the task, update it
        $reminder = $task->reminder;
        if ($reminder) {
            $reminder->update([
                'user_id' => $user?->id,
                'remind_at' => $data['remind_at'],
                'note' => $data['note'] ?? null,
                'title' => $task->task_name,
            ]);
        } else {
            Reminder::create([
                'task_id' => $task->id,
                'user_id' => $user?->id,
                'title' => $task->task_name,
                'note' => $data['note'] ?? null,
                'remind_at' => $data['remind_at'],
            ]);
        }

        return back()->with('status', 'Reminder untuk task disimpan.');
    }

    public function destroy(Reminder $reminder)
    {
        $reminder->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['status' => 'ok']);
        }

        return back()->with('status', 'Reminder dihapus');
    }
}
