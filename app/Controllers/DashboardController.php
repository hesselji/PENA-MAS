<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\OutgoingLetter;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        if (Auth::isIncoming()) {
            header('Location: /incoming');
            exit;
        }
        View::render('dashboard/index', OutgoingLetter::dashboardStats());
    }
}
