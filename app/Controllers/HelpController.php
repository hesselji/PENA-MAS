<?php
namespace App\Controllers;
use App\Core\Auth;use App\Core\View;
final class HelpController{public function index(): void{Auth::requireLogin();View::render('help/index');}}
