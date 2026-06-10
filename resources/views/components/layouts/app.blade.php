<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Todo') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-zinc-900">
    <div class="pointer-events-none fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-b from-green-50 via-white to-white"></div>
    </div>

    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <header class="flex items-center justify-between py-8">
            <div>
                <p class="text-xs font-semibold tracking-wide text-green-700">PUBLIC · SINGLE USER</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">Todo List</h1>
                <p class="mt-1 text-sm text-zinc-600">Minimal, card-based, deadline-aware.</p>
            </div>

            <div class="flex items-center gap-2">
                {{ $headerActions ?? '' }}
                {{-- Notifications removed from header (deadline notifications available above Add task) --}}
            </div>
        </header>

        <main class="pb-12">
            {{ $slot }}
        </main>
        
    </div>
</body>
</html>

<script>
    // Global handler for forms/buttons marked with data-ajax-delete
    (function () {
        function closest(el, selector) {
            while (el) {
                if (el.matches && el.matches(selector)) return el;
                el = el.parentElement;
            }
            return null;
        }

        function clearFormErrors(form) {
            form.querySelectorAll('.validation-error').forEach(n => n.remove());
            form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
        }

        function showFormErrors(form, errors) {
            Object.keys(errors).forEach(function (field) {
                const input = form.querySelector('[name="' + field + '"]');
                const messages = errors[field];
                if (!input) return;
                input.classList.add('is-invalid');
                const el = document.createElement('div');
                el.className = 'validation-error mt-1 text-xs text-red-600';
                el.textContent = messages.join(' ');
                input.insertAdjacentElement('afterend', el);
            });
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest && e.target.closest('[data-ajax-delete]');
            if (!btn) return;

            // confirm handled by existing onclick if any
            const form = btn.closest('form');
            if (!form) return;

            e.preventDefault();

            const confirmMsg = btn.getAttribute('data-confirm') || null;
            if (confirmMsg && !confirm(confirmMsg)) return;

            const action = form.getAttribute('action');
            const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'POST').toUpperCase();
            const formData = new FormData(form);

            fetch(action, {
                method: method === 'DELETE' ? 'POST' : method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value || ''
                },
                body: formData,
            }).then(async r => {
                if (r.status === 422) {
                    const json = await r.json();
                    clearFormErrors(form);
                    showFormErrors(form, json.errors || {});
                    return Promise.reject({ validation: true });
                }
                if (!r.ok) {
                    const txt = await r.text();
                    return Promise.reject(new Error(txt || 'Delete failed'));
                }
                return r.json().catch(() => ({}));
            }).then(data => {
                // remove target element if specified
                const removeSpec = form.getAttribute('data-remove') || btn.getAttribute('data-remove');
                let removedEl = null;
                if (removeSpec) {
                    const parts = removeSpec.split(':');
                    const how = parts[0];
                    const selector = parts.slice(1).join(':');
                    if (how === 'closest') {
                        const el = closest(btn, selector);
                        if (el) {
                            removedEl = el;
                            el.remove();
                        }
                    } else {
                        // fallback: remove form's parent
                        removedEl = form.parentElement;
                        if (removedEl) removedEl.remove();
                    }
                }

                // If a task card was removed, update the total and show empty placeholder when needed
                try {
                    const tasksCountEl = document.querySelector('[data-tasks-count]');
                    const tasksListEl = document.querySelector('[data-tasks-list]');
                    if (removedEl && removedEl.hasAttribute && removedEl.hasAttribute('data-task-id')) {
                        if (tasksCountEl) {
                            let n = parseInt((tasksCountEl.textContent || '').trim()) || 0;
                            n = Math.max(0, n - 1);
                            tasksCountEl.textContent = n + ' total';
                        }

                        const remaining = document.querySelectorAll('[data-task-id]').length;
                        if (remaining === 0 && tasksListEl) {
                            tasksListEl.innerHTML = '<div class="rounded-2xl border border-dashed border-zinc-200 p-10 text-center">' +
                                '<p class="text-sm font-semibold">No tasks</p>' +
                                '<p class="mt-1 text-sm text-zinc-600">No tasks — add one from the form.</p>' +
                                '</div>';
                        }
                    }
                } catch (err) {
                    // ignore DOM update errors
                    console.error(err);
                }

                // show a small toast
                const t = document.createElement('div');
                t.className = 'fixed bottom-6 right-6 rounded-xl bg-white border border-zinc-200 px-4 py-3 shadow-lg';
                t.textContent = 'Removed.';
                document.body.appendChild(t);
                setTimeout(() => t.remove(), 3000);
            }).catch(err => {
                if (err && err.validation) return; // already shown
                console.error(err);
                alert('Delete failed');
            });
        }, false);

        // Handle Set time buttons next to datetime-local inputs
        document.addEventListener('click', function (e) {
            const btn = e.target.closest && e.target.closest('[data-set-time]');
            if (!btn) return;

            // find nearest datetime-local input in the same container
            const container = btn.closest('div') || btn.closest('form') || document;
            let input = null;
            if (btn.getAttribute('data-target')) {
                input = document.querySelector(btn.getAttribute('data-target'));
            } else {
                input = container.querySelector('input[type="datetime-local"]');
            }

            if (!input) {
                alert('No datetime input found');
                return;
            }

            const val = input.value;
            if (!val) {
                // show a small toast prompting to pick a time
                const t = document.createElement('div');
                t.className = 'fixed bottom-6 right-6 rounded-xl bg-white border border-zinc-200 px-4 py-3 shadow-lg';
                t.textContent = 'Please pick a time first.';
                document.body.appendChild(t);
                setTimeout(() => t.remove(), 3000);
                return;
            }

            // mark as confirmed and show confirmation text
            input.dataset.timeConfirmed = '1';
            let confirmEl = container.querySelector('.time-confirmation');
            if (!confirmEl) {
                confirmEl = document.createElement('div');
                // show confirmation as a block below the whole grid row so it doesn't overlap neighbouring column
                confirmEl.className = 'time-confirmation text-xs text-zinc-600 mt-2 w-full';

                // find nearest grid row (the parent with Tailwind classes 'grid' and 'sm:grid-cols-2')
                let ancestor = btn.parentElement;
                while (ancestor && ancestor !== document.body) {
                    try {
                        if (ancestor.classList && (ancestor.classList.contains('sm:grid-cols-2') || ancestor.classList.contains('grid'))) {
                            // ensure this grid actually contains the datetime input
                            if (ancestor.querySelector && ancestor.querySelector('input[type="datetime-local"]')) break;
                        }
                    } catch (err) {
                        // ignore
                    }
                    ancestor = ancestor.parentElement;
                }

                const insertTarget = ancestor && ancestor !== document.body ? ancestor : (btn.closest('.mt-1') || btn.parentElement || container);
                insertTarget.insertAdjacentElement('afterend', confirmEl);
            }
            confirmEl.textContent = 'Set: ' + val.replace('T', ' ');
        }, false);

        // Handle AJAX update forms
        document.addEventListener('submit', function (e) {
            const form = e.target.closest && e.target.closest('form[data-ajax-update]');
            if (!form) return;
            e.preventDefault();

            const action = form.getAttribute('action');
            const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'POST').toUpperCase();
            const formData = new FormData(form);

            clearFormErrors(form);
            fetch(action, {
                method: method === 'PATCH' ? 'POST' : method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value || ''
                },
                body: formData,
            }).then(async r => {
                if (r.status === 422) {
                    const json = await r.json();
                    showFormErrors(form, json.errors || {});
                    return Promise.reject({ validation: true });
                }
                if (!r.ok) {
                    const txt = await r.text();
                    return Promise.reject(new Error(txt || 'Update failed'));
                }
                return r.json().catch(() => ({}));
            }).then(data => {
                // find the card by data-task-id
                const card = document.querySelector('[data-task-id="' + data.id + '"]');
                if (!card) return;
                if (data.task_name) {
                    const el = card.querySelector('[data-task-name]'); if (el) el.textContent = data.task_name;
                }
                if (data.status) {
                    const el = card.querySelector('[data-task-status]'); if (el) el.textContent = data.status;
                }
                if (data.deadline !== undefined) {
                    const el = card.querySelector('[data-task-deadline-full]'); if (el) el.textContent = data.deadline ? ('Deadline: ' + data.deadline) : 'Deadline: —';
                }
                if (data.category !== undefined) {
                    const el = card.querySelector('[data-task-category]'); if (el) el.textContent = data.category || '';
                }
                if (data.subject !== undefined) {
                    const el = card.querySelector('[data-task-subject]'); if (el) el.textContent = data.subject || '';
                }

                // hide editing panel only on success
                const alpine = card.__x; // optional
                const editingEl = card.querySelector('[x-data]');
                if (editingEl) {
                    const ev = new CustomEvent('toggle-editing', { detail: { id: data.id } });
                    document.dispatchEvent(ev);
                }
                // small toast
                const t = document.createElement('div');
                t.className = 'fixed bottom-6 right-6 rounded-xl bg-white border border-zinc-200 px-4 py-3 shadow-lg';
                t.textContent = 'Saved.';
                document.body.appendChild(t);
                setTimeout(() => t.remove(), 3000);

                // Update notification badges and nearest-deadlines from server response when provided
                try {
                    function escapeHtml(str) {
                        if (!str) return '';
                        return str.replace(/[&<>"'`]/g, function (s) {
                            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;","`":"&#96;"})[s];
                        });
                    }

                    if (data.counts) {
                        const badgesEl = document.querySelector('[data-deadline-badges]');
                        if (badgesEl) {
                            let html = '';
                            if ((data.counts.overdueCount || 0) > 0) {
                                html += '<span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200">Overdue: ' + (data.counts.overdueCount || 0) + '</span>';
                            }
                            if ((data.counts.dueSoonCount || 0) > 0) {
                                html += '<span class="inline-flex items-center rounded-full due-soon px-3 py-1 text-xs font-semibold">Due in 24h: ' + (data.counts.dueSoonCount || 0) + '</span>';
                            }
                            badgesEl.innerHTML = html;
                        }
                    }

                    if (data.nearestDeadlines && Array.isArray(data.nearestDeadlines)) {
                        const ndEl = document.querySelector('[data-nearest-deadlines]');
                        if (ndEl) {
                            let html = '';
                            data.nearestDeadlines.forEach(function (d) {
                                let classes = 'px-2 py-1 rounded-md border flex items-center justify-between';
                                if (d.status === 'overdue') classes += ' bg-red-50 text-red-700 ring-1 ring-inset ring-red-200';
                                else if (d.status === 'due_soon') classes += ' due-soon';
                                else classes += ' bg-zinc-50 text-zinc-700';

                                html += '<div class="' + classes + '">';
                                html += '<div class="font-medium">' + escapeHtml(d.task_name) + '</div>';
                                html += '<div class="text-xs text-zinc-500 ml-4">' + (d.due_countdown || '') + '</div>';
                                html += '</div>';
                            });
                            ndEl.innerHTML = html;
                        }
                    }
                } catch (err) {
                    console.error('Error updating notification UI', err);
                }
            }).catch(err => {
                if (err && err.validation) return; // already shown
                console.error(err);
                alert('Update failed');
            });
        }, false);
    })();
</script>
