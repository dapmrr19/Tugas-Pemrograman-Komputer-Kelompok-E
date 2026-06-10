<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'title',
        'note',
        'remind_at',
        'is_sent',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
