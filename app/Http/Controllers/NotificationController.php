<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function markAsRead($id)
    {
        DB::table('notifications')->where('id', $id)->update(['read_at' => now()]);

        return back();
    }
}
