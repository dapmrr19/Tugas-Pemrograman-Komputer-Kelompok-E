<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ReminderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TaskController::class, 'index'])->name('tasks.index');

Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
Route::patch('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

// Reminders (per-task)
Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');

// Reminder per task
Route::post('/tasks/{task}/reminder', [ReminderController::class, 'storeForTask'])->name('tasks.reminder.store');

// Notifications
use App\Http\Controllers\NotificationController;
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
