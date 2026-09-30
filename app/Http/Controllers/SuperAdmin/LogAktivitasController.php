<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogAktivitasController extends Controller
{
    /**
     * Menampilkan daftar log aktivitas (audit trail) sistem.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $filters = [
            'search' => $request->query('search', ''),
            'action' => $request->query('action', ''),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = LogActivity::query()
            ->with(['user:id,name,username,role'])
            ->latest();

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $logs = $query->paginate($filters['per_page'])->withQueryString();

        $actionsList = LogActivity::select('action')
            ->distinct()
            ->whereNotNull('action')
            ->orderBy('action')
            ->pluck('action');

        return \Inertia\Inertia::render('SuperAdmin/Logs/Index', [
            'user' => $user,
            'logs' => $logs,
            'filters' => $filters,
            'actionsList' => $actionsList,
            'stats' => [
                'total_logs' => LogActivity::count(),
                'total_today' => LogActivity::whereDate('created_at', now())->count(),
            ],
        ]);
    }
}
