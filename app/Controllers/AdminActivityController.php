<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\User;

final class AdminActivityController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $filters = [
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to'   => trim((string) ($_GET['date_to'] ?? '')),
            'user_id'   => (int) ($_GET['user_id'] ?? 0),
            'action'    => trim((string) ($_GET['action'] ?? '')),
        ];

        View::render('admin/activity/index', [
            'activities' => AuditLog::history($filters),
            'filters' => $filters,
            'users' => User::all(),
            'activityTypes' => AuditLog::activityTypes(),
        ]);
    }
}
