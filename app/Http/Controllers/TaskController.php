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
            ->with(['category', 'subject'])
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

        return view('tasks.index', [
            'tasks' => $tasks,
            'categories' => $categories,
            'subjects' => $subjects,
            'nowIso' => $now->toIso8601String(),
            'dueSoonCount' => $dueSoonCount,
            'overdueCount' => $overdueCount,
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

    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'task_name' => ['required', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'status' => ['required', 'string', 'in:' . implode(',', Task::STATUSES)],
        ]);

        $task->update($validated);

        return back()->with('status', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back()->with('status', 'Task deleted.');
    }
}
