<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subject;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(): View
    {
        $now = now();
        $soon = $now->copy()->addDay();

        $tasks = Task::query()
            ->with(['category', 'subject', 'reminder'])
            ->orderByRaw('deadline is null')
            ->orderBy('deadline')
            ->orderByDesc('id')
            ->get();

        $categories = Category::query()->orderBy('name')->get();
        $subjects = Subject::query()->orderBy('name')->get();

        $dueSoonCount = Task::query()
            ->where('status', '!=', Task::STATUS_DONE)
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$now, $soon])
            ->count();

        $overdueCount = Task::query()
            ->where('status', '!=', Task::STATUS_DONE)
            ->whereNotNull('deadline')
            ->where('deadline', '<', $now)
            ->count();

        // nearest deadlines (2) and full list for details
        $nearest = Task::query()
            ->whereNotNull('deadline')
            ->orderBy('deadline')
            ->take(2)
            ->get();

        $allDeadlines = Task::query()
            ->whereNotNull('deadline')
            ->orderBy('deadline')
            ->get();

        // helper: produce countdown string like "Xd,Yh,Zm" or "Overdue Xd,Yh,Zm"
        $formatCountdown = function ($target) use ($now) {
            if (! $target) return null;
            $diff = $target->diffInSeconds($now, false);
            $abs = abs($diff);
            $days = intdiv($abs, 86400);
            $hours = intdiv($abs % 86400, 3600);
            $minutes = intdiv($abs % 3600, 60);
            $str = sprintf('%sd %sh %sm', $days, $hours, $minutes);
            return $diff < 0 ? ('Overdue ' . $str) : $str;
        };

        $nearestData = $nearest->map(function ($t) use ($formatCountdown) {
            return [
                'id' => $t->id,
                'task_name' => $t->task_name,
                'deadline' => $t->deadline?->format('Y-m-d H:i'),
                'due_countdown' => $formatCountdown($t->deadline),
            ];
        });

        $allDeadlinesData = $allDeadlines->map(function ($t) use ($formatCountdown) {
            return [
                'id' => $t->id,
                'task_name' => $t->task_name,
                'deadline' => $t->deadline?->format('Y-m-d H:i'),
                'due_countdown' => $formatCountdown($t->deadline),
            ];
        });

        return view('tasks.index', [
            'tasks' => $tasks,
            'categories' => $categories,
            'subjects' => $subjects,
            'nowIso' => $now->toIso8601String(),
            'dueSoonCount' => $dueSoonCount,
            'overdueCount' => $overdueCount,
            'nearestDeadlines' => $nearestData,
            'allDeadlines' => $allDeadlinesData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_name' => ['required', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'status' => ['required', 'string', 'in:' . implode(',', Task::STATUSES)],
        ]);

        Task::create($validated);

        return back()->with('status', 'Task created.');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'task_name' => ['required', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'status' => ['required', 'string', 'in:' . implode(',', Task::STATUSES)],
        ]);

        $task->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            $task->load(['category','subject','reminder']);
            return response()->json([
                'id' => $task->id,
                'task_name' => $task->task_name,
                'status' => $task->status,
                'deadline' => $task->deadline ? $task->deadline->format('Y-m-d H:i') : null,
                'category' => $task->category?->name,
                'subject' => $task->subject?->name,
                'reminder' => $task->reminder ? $task->reminder->remind_at->format('Y-m-d H:i') : null,
            ]);
        }

        return back()->with('status', 'Task updated.');
    }

    public function destroy(Request $request, Task $task)
    {
        // delete associated reminder and its in-app notifications (if any)
        $reminder = $task->reminder;
        if ($reminder) {
            // remove notifications that reference this reminder id in data JSON
            \Illuminate\Support\Facades\DB::table('notifications')
                ->where('data', 'like', '%"reminder_id":' . $reminder->id . '%')
                ->delete();

            $reminder->delete();
        }

        $task->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'ok']);
        }

        return back()->with('status', 'Task deleted.');
    }
}
