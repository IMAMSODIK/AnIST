<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditTrail::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $trails = $query->orderBy('created_at', 'desc')->paginate(20);

        $actions = AuditTrail::distinct()->pluck('action');

        return view('audit-trail.index', compact('trails', 'actions'));
    }

    public function show(AuditTrail $auditTrail)
    {
        $auditTrail->load('user');
        return view('audit-trail.show', compact('auditTrail'));
    }
}
