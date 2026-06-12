@php
    use App\Models\Task;
    use Illuminate\Support\Carbon;

    $statusLabels = [
        Task::STATUS_TODO => 'To Do',
        Task::STATUS_IN_PROGRESS => 'In Progress',
        Task::STATUS_DONE => 'Done',
    ];

    $now = Carbon::parse($nowIso);
@endphp

<x-layouts.app>
    <x-slot:headerActions>
        <button
            type="button"
            class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-900 hover:border-zinc-300"
            x-data
            @click="$dispatch('open-modal', { name: 'categories' })"
        >
            Manage Categories
        </button>
        <button
            type="button"
            class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-900 hover:border-zinc-300"
            x-data
            @click="$dispatch('open-modal', { name: 'subjects' })"
        >
            Manage Subjects
        </button>
    </x-slot:headerActions>

    <div class="space-y-6" x-data="{ now: Date.parse(@js($nowIso)) }" x-init="setInterval(() => now = Date.now(), 60_000)">
        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($overdueCount > 0 || $dueSoonCount > 0)
            <div class="rounded-2xl border border-zinc-200 bg-white p-4" x-data="{ open:false }">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold">Deadline notifications</p>
                        <p class="text-xs text-zinc-600">Click view on the right to see details</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex flex-wrap gap-2" data-deadline-badges>
                            @if ($overdueCount > 0)
                                <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                    Overdue: {{ $overdueCount }}
                                </span>
                            @endif
                            @if ($dueSoonCount > 0)
                                <span class="inline-flex items-center rounded-full due-soon px-3 py-1 text-xs font-semibold">
                                    Due in 24h: {{ $dueSoonCount }}
                                </span>
                            @endif
                        </div>

                        {{-- show two nearest deadlines front --}}
                        <div class="flex flex-col text-xs text-zinc-700" data-nearest-deadlines>
                            @foreach ($nearestDeadlines ?? [] as $d)
                                @php
                                    $classes = 'px-2 py-1 rounded-md border flex items-center justify-between';
                                    if (($d['status'] ?? '') === 'overdue') {
                                        $classes = $classes . ' bg-red-50 text-red-700 ring-1 ring-inset ring-red-200';
                                    } elseif (($d['status'] ?? '') === 'due_soon') {
                                        $classes = $classes . ' due-soon';
                                    } else {
                                        $classes = $classes . ' bg-zinc-50 text-zinc-700';
                                    }
                                @endphp
                                <div class="{{ $classes }}" data-deadline-ms="{{ $d['deadline_ms'] ?? '' }}">
                                    <div class="font-medium">{{ $d['task_name'] }}</div>
                                    <div class="text-xs text-zinc-500 ml-4" data-countdown>{{ $d['due_countdown'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-900 hover:border-zinc-300" @click="open = !open">View</button>
                    </div>
                </div>

                {{-- modal / dropdown for details --}}
                <div x-show="open" x-cloak class="mt-3 rounded-lg border border-zinc-100 bg-white p-3 shadow">
                    <div class="text-sm font-semibold">All deadlines</div>
                    <div class="mt-2 space-y-2 text-sm">
                        @foreach ($allDeadlines ?? [] as $d)
                            @php
                                $deadlineMs = $d['deadline_ms'] ?? (isset($d['deadline']) && $d['deadline'] ? \Illuminate\Support\Carbon::parse($d['deadline'])->getTimestampMs() : null);
                            @endphp
                            <div x-data="{ deadline: {{ $deadlineMs ?? 'null' }} }" :class="(() => {
                                const dl = deadline;
                                if (!dl) return '';
                                const diff = dl - now;
                                if (diff < 0) return 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200';
                                if (diff <= 24*60*60*1000) return 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200';
                                return 'bg-zinc-50 text-zinc-700 ring-1 ring-inset ring-zinc-200';
                            })()" class="rounded-lg border border-zinc-100 p-2 flex items-start justify-between">
                                <div>
                                    <div class="font-medium">{{ $d['task_name'] }}</div>
                                    <div class="text-xs text-zinc-500">Deadline: {{ $d['deadline'] }}</div>
                                </div>
                                <div class="text-xs font-semibold" x-text="(() => {
                                    const dl = deadline;
                                    if (!dl) return '—';
                                    const diff = dl - now;
                                    if (diff < 0) return 'Overdue';
                                    const s = Math.floor(diff / 1000);
                                    const days = Math.floor(s / 86400);
                                    const hours = Math.floor((s % 86400) / 3600);
                                    const minutes = Math.floor((s % 3600) / 60);
                                    if (days > 0) return `${days}d ${hours}h ${minutes}m`;
                                    if (hours > 0) return `${hours}h ${minutes}m`;
                                    return `${minutes}m`;
                                })()"></div>
                            </div>
                        @endforeach
                        @if (empty($allDeadlines) || collect($allDeadlines)->isEmpty())
                            <div class="text-xs text-zinc-500">No deadlines</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-12">
            <section class="lg:col-span-5">
                <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                    <h2 class="text-base font-semibold">Add task</h2>
                    <p class="mt-1 text-sm text-zinc-600">Create tasks with deadline, category, subject, and status.</p>

                    <form method="POST" action="{{ route('tasks.store') }}" class="mt-5 space-y-4">
                        @csrf

                        <div>
                            <label class="text-sm font-medium" for="task_name">Task Name</label>
                            <input
                                id="task_name"
                                name="task_name"
                                value="{{ old('task_name') }}"
                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                placeholder="e.g. Finish report"
                                required
                            />
                            @error('task_name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium" for="deadline">Deadline</label>
                                <div class="mt-1 flex items-center gap-2 min-w-0">
                                        <input
                                            id="deadline"
                                            type="datetime-local"
                                            name="deadline"
                                            value="{{ old('deadline') }}"
                                            class="flex-1 min-w-0 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                        />
                                    <button type="button" data-set-time class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 flex-shrink-0">Set</button>
                                </div>
                                @error('deadline')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="text-sm font-medium" for="status">Status</label>
                                <select
                                    id="status"
                                    name="status"
                                    class="mt-1 w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                >
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', Task::STATUS_TODO) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium" for="category_id">Category</label>
                                <select
                                    id="category_id"
                                    name="category_id"
                                    class="mt-1 w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                >
                                    <option value="">—</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="text-sm font-medium" for="subject_id">Subject</label>
                                <select
                                    id="subject_id"
                                    name="subject_id"
                                    class="mt-1 w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                >
                                    <option value="">—</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                                @error('subject_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-2">
                            <button
                                type="submit"
                                class="cursor-pointer w-full rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                            >
                                Add Task
                            </button>
                            <p class="text-xs text-zinc-500">Tip: create categories/subjects from the buttons above.</p>
                        </div>
                    </form>
                </div>
            </section>

            <section class="lg:col-span-7">
                <div class="bg-transparent p-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('images/book-icon.svg') }}" alt="Books" class="w-6 h-6" />
                            <h2 class="text-base font-semibold">Tasks</h2>
                        </div>
                        <p class="text-sm text-zinc-600" data-tasks-count>{{ $tasks->count() }} total</p>
                    </div>

                    <div class="mt-4 space-y-6" data-tasks-list>
                        @forelse ($tasks as $task)
                            @php
                                $deadlineMs = $task->deadline?->getTimestampMs();
                                $isDone = $task->status === Task::STATUS_DONE;
                                $isOverdue = !$isDone && $task->deadline && $task->deadline->lt($now);
                                $isDueSoon = !$isDone && $task->deadline && $task->deadline->gte($now) && $task->deadline->lte($now->copy()->addDay());
                            @endphp

                            <div class="rounded-2xl border border-zinc-200 p-4" x-data="{ editing: false, done: {{ $isDone ? 'true' : 'false' }} }" data-task-id="{{ $task->id }}">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <template x-if="true">
                                                <svg x-show="done" x-transition:enter="transition transform duration-200" x-transition:enter-start="opacity-0 scale-75 translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition transform duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-75" class="w-5 h-5 text-green-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </template>

                                            <p class="truncate text-sm font-semibold" :class="done ? 'line-through text-zinc-400' : ''" data-task-name>{{ $task->task_name }}</p>

                                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700" data-task-status>
                                                {{ $statusLabels[$task->status] ?? $task->status }}
                                            </span>

                                            <template x-if="{{ $task->deadline ? 'true' : 'false' }}">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                                    :class="(() => {
                                                        const deadline = {{ $deadlineMs ? $deadlineMs : 'null' }};
                                                        if (!deadline) return 'bg-zinc-100 text-zinc-700 ring-zinc-200';
                                                        const isDone = {{ $isDone ? 'true' : 'false' }};
                                                        if (isDone) return 'bg-zinc-100 text-zinc-700 ring-zinc-200';
                                                        const diff = deadline - now;
                                                        if (diff < 0) return 'bg-red-50 text-red-700 ring-red-200';
                                                        if (diff <= 2 * 60 * 60 * 1000) return 'due-soon';
                                                        return 'bg-green-50 text-green-700 ring-green-200';
                                                    })()"
                                                >
                                                    <span data-task-deadline
                                                        x-text="(() => {
                                                            const deadline = {{ $deadlineMs ? $deadlineMs : 'null' }};
                                                            if (!deadline) return 'No deadline';
                                                            const diff = deadline - now;
                                                            if (diff < 0) return 'Overdue';
                                                            if (diff <= 2 * 60 * 60 * 1000) return 'Due soon';
                                                            return 'Scheduled';
                                                        })()"
                                                    ></span>
                                                </span>
                                            </template>
                                        </div>

                                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-zinc-600">
                                            @if ($task->category)
                                                <span class="rounded-full bg-green-50 px-2.5 py-1 font-medium text-green-800 ring-1 ring-inset ring-green-200" data-task-category>{{ $task->category->name }}</span>
                                            @endif
                                            @if ($task->subject)
                                                <span class="rounded-full bg-zinc-100 px-2.5 py-1 font-medium text-zinc-700 ring-1 ring-inset ring-zinc-200" data-task-subject>{{ $task->subject->name }}</span>
                                            @endif
                                            @if ($task->deadline)
                                                <span class="px-1" data-task-deadline-full>Deadline: {{ $task->deadline->format('Y-m-d H:i') }}</span>
                                            @else
                                                <span class="px-1" data-task-deadline-full>Deadline: —</span>
                                            @endif
                                            @if ($task->reminder)
                                                <span class="px-1">Reminder: {{ $task->reminder->remind_at->format('Y-m-d H:i') }}</span>
                                            @endif
                                            
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        <button
                                            type="button"
                                            class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold hover:border-zinc-300"
                                            @click="editing = !editing"
                                        >
                                            Edit
                                        </button>

                                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="sm:ml-2" data-ajax-delete data-remove="closest:.rounded-2xl.border.p-4">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:border-zinc-300"
                                                onclick="return confirm('Delete this task?')"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="mt-4" x-show="editing" x-cloak>
                                    <form method="POST" action="{{ route('tasks.update', $task) }}" class="grid gap-3 sm:grid-cols-2" data-ajax-update>
                                        @csrf
                                        @method('PATCH')

                                        <div class="sm:col-span-2">
                                            <label class="text-xs font-semibold text-zinc-700">Task Name</label>
                                            <input
                                                name="task_name"
                                                value="{{ $task->task_name }}"
                                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                                required
                                            />
                                        </div>

                                        <div>
                                            <label class="text-xs font-semibold text-zinc-700">Deadline</label>
                                            <input
                                                type="datetime-local"
                                                name="deadline"
                                                value="{{ $task->deadline ? $task->deadline->format('Y-m-d\\TH:i') : '' }}"
                                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                            />
                                        </div>

                                        

                                        <div>
                                            <label class="text-xs font-semibold text-zinc-700">Status</label>
                                                <select
                                                    name="status"
                                                    @change="done = $event.target.value === '{{ Task::STATUS_DONE }}'"
                                                    class="mt-1 w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                                >
                                                @foreach ($statusLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="text-xs font-semibold text-zinc-700">Category</label>
                                            <select
                                                name="category_id"
                                                class="mt-1 w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                            >
                                                <option value="">—</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}" @selected($task->category_id === $category->id)>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="text-xs font-semibold text-zinc-700">Subject</label>
                                            <select
                                                name="subject_id"
                                                class="mt-1 w-full cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                            >
                                                <option value="">—</option>
                                                @foreach ($subjects as $subject)
                                                    <option value="{{ $subject->id }}" @selected($task->subject_id === $subject->id)>{{ $subject->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="sm:col-span-2 flex items-center gap-2">
                                            <button type="submit" class="cursor-pointer rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">Save</button>
                                            <button type="button" class="cursor-pointer rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold" @click="editing = false">Cancel</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="mt-3" x-data="{ showReminder: false }" data-task-reminder>
                                    @if ($task->reminder)
                                        <div class="flex items-center gap-2">
                                            <div class="text-sm text-zinc-700">Reminder set: {{ $task->reminder->remind_at->format('Y-m-d H:i') }}</div>
                                            <form method="POST" action="{{ route('reminders.destroy', $task->reminder) }}" data-ajax-delete data-remove="closest:[data-task-reminder]">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-semibold text-red-700">Remove</button>
                                            </form>
                                            <button type="button" class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs" @click="showReminder = !showReminder">Edit</button>
                                        </div>
                                    @else
                                        <button type="button" class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs" @click="showReminder = true">Set Reminder</button>
                                    @endif

                                    <div x-show="showReminder" x-cloak class="mt-2">
                                        <form method="POST" action="{{ route('tasks.reminder.store', $task) }}" class="flex flex-wrap gap-2 items-center">
                                            @csrf
                                            <input type="datetime-local" name="remind_at" required class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm min-w-0" />
                                            <input type="text" name="note" placeholder="Note (optional)" class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm min-w-0 flex-1" />
                                            <button type="submit" class="cursor-pointer rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white shrink-0">Save</button>
                                            <button type="button" class="cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm shrink-0" @click="showReminder = false">Cancel</button>
                                        </form>
                                    </div>
                            </div>
                        </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-zinc-200 p-10 text-center">
                                <p class="text-sm font-semibold">No tasks</p>
                                <p class="mt-1 text-sm text-zinc-600">No tasks — add one from the form.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        {{-- Modal: Categories --}}
        <div
            x-data="modalController('categories')"
            x-on:open-modal.window="onOpen($event)"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            aria-modal="true"
            role="dialog"
        >
            <div class="absolute inset-0 bg-zinc-900/40" @click="open = false"></div>
            <div class="relative w-full max-w-xl rounded-2xl border border-zinc-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold">Categories</h3>
                        <p class="mt-1 text-sm text-zinc-600">Add, edit, or delete categories directly from UI.</p>
                    </div>
                    <button type="button" class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold" @click="open = false">Close</button>
                </div>

                <form method="POST" action="{{ route('categories.store') }}" class="mt-4 flex gap-2">
                    @csrf
                    <input
                        name="name"
                        class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                        placeholder="New category name"
                        required
                    />
                    <button class="cursor-pointer rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">Add</button>
                </form>

                <div class="mt-4 space-y-2">
                    @foreach ($categories as $category)
                        <div class="rounded-xl border border-zinc-200 p-3">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <form method="POST" action="{{ route('categories.update', $category) }}" class="flex flex-1 items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input
                                        name="name"
                                        value="{{ $category->name }}"
                                        class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                        required
                                    />
                                    <button class="cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold hover:border-zinc-300">Save</button>
                                </form>

                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="sm:ml-2">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:border-zinc-300"
                                        onclick="return confirm('Delete this category? (Tasks will keep working; category becomes empty)')"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    @if ($categories->isEmpty())
                        <p class="text-sm text-zinc-600">No categories yet.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Modal: Subjects --}}
        <div
            x-data="modalController('subjects')"
            x-on:open-modal.window="onOpen($event)"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            aria-modal="true"
            role="dialog"
        >
            <div class="absolute inset-0 bg-zinc-900/40" @click="open = false"></div>
            <div class="relative w-full max-w-xl rounded-2xl border border-zinc-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold">Subjects</h3>
                        <p class="mt-1 text-sm text-zinc-600">Add, edit, or delete subjects directly from UI.</p>
                    </div>
                    <button type="button" class="cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold" @click="open = false">Close</button>
                </div>

                <form method="POST" action="{{ route('subjects.store') }}" class="mt-4 flex gap-2">
                    @csrf
                    <input
                        name="name"
                        class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                        placeholder="New subject name"
                        required
                    />
                    <button class="cursor-pointer rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">Add</button>
                </form>

                <div class="mt-4 space-y-2">
                    @foreach ($subjects as $subject)
                        <div class="rounded-xl border border-zinc-200 p-3">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <form method="POST" action="{{ route('subjects.update', $subject) }}" class="flex flex-1 items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input
                                        name="name"
                                        value="{{ $subject->name }}"
                                        class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
                                        required
                                    />
                                    <button class="cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold hover:border-zinc-300">Save</button>
                                </form>

                                <form method="POST" action="{{ route('subjects.destroy', $subject) }}" class="sm:ml-2">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="cursor-pointer rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:border-zinc-300"
                                        onclick="return confirm('Delete this subject? (Tasks will keep working; subject becomes empty)')"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    @if ($subjects->isEmpty())
                        <p class="text-sm text-zinc-600">No subjects yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <script>
            function modalController(name) {
                return {
                    name,
                    open: false,
                    onOpen(event) {
                        if (!event?.detail?.name) return;
                        this.open = event.detail.name === this.name;
                    },
                };
            }
        </script>
    </div>
</x-layouts.app>
