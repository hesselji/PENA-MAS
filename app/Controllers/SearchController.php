<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\GlobalSearch;

final class SearchController
{
    public function index(): void
    {
        Auth::requireLogin();
        $q=trim($_GET['q']??'');
        View::render('search/index',['q'=>$q,'results'=>GlobalSearch::run($q)]);
    }
}
