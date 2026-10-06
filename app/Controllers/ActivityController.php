<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\User;

final class ActivityController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $filters=['q'=>trim($_GET['q']??''),'user_id'=>(int)($_GET['user_id']??0),'date_from'=>$_GET['date_from']??'','date_to'=>$_GET['date_to']??''];
        View::render('activity/index',['rows'=>AuditLog::search($filters,250),'filters'=>$filters,'users'=>User::all()]);
    }
}
