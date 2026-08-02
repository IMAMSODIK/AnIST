<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\Upload;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __invoke()
    {
        $notifications = AuditTrail::with('user')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereIn('action', [
                'upload_evidence',
                'response_ai',
                'calculate_kpi',
                'upload_delete',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($trail) => [
                'id' => $trail->id,
                'action' => $this->formatAction($trail->action),
                'icon' => $this->getActionIcon($trail->action),
                'color' => $this->getActionColor($trail->action),
                'message' => $this->formatMessage($trail),
                'time' => $trail->created_at->diffForHumans(),
            ]);

        $unreadCount = $notifications->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    protected function formatAction(string $action): string
    {
        return match ($action) {
            'upload_evidence' => 'Evidence Uploaded',
            'response_ai' => 'AI Analysis Completed',
            'calculate_kpi' => 'KPI Calculated',
            'upload_delete' => 'Evidence Deleted',
            default => $action,
        };
    }

    protected function getActionIcon(string $action): string
    {
        return match ($action) {
            'upload_evidence' => 'upload',
            'response_ai' => 'ai',
            'calculate_kpi' => 'chart',
            'upload_delete' => 'trash',
            default => 'info',
        };
    }

    protected function getActionColor(string $action): string
    {
        return match ($action) {
            'upload_evidence' => 'indigo',
            'response_ai' => 'violet',
            'calculate_kpi' => 'emerald',
            'upload_delete' => 'rose',
            default => 'slate',
        };
    }

    protected function formatMessage(AuditTrail $trail): string
    {
        $userName = $trail->user?->name ?? 'System';

        return match ($trail->action) {
            'upload_evidence' => "{$userName} uploaded a new evidence",
            'response_ai' => "AI analysis completed for evidence #{$trail->model_id}",
            'calculate_kpi' => "KPI scores recalculated",
            'upload_delete' => "{$userName} deleted evidence #{$trail->model_id}",
            default => "Activity recorded",
        };
    }
}
