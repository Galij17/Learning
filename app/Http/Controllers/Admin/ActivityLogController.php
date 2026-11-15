<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = ActivityLog::query()
            ->with('user:id,name,email')
            ->when($request->string('search')->toString(), fn ($q, $s) => $q
                ->where('description', 'like', "%{$s}%")
                ->orWhere('action', 'like', "%{$s}%"))
            ->when($request->string('action')->toString(), fn ($q, $a) => $q->where('action', 'like', "{$a}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user?->name ?? 'Sistem',
                'user_email' => $log->user?->email ?? '-',
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->format('d M Y H:i:s'),
            ]);

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'action']),
        ]);
    }
}
