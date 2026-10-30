<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Forbidden. Audit log is restricted to system administrators.');
        }

        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', 'like', "%{$request->auditable_type}%");
        }

        $auditLogs = $query->latest('created_at')->paginate(15)->withQueryString();

        return view('audit.index', compact('auditLogs'));
    }
}
