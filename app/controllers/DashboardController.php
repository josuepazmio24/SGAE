<?php
require_once __DIR__ . '/../core/Controller.php';


class DashboardController extends Controller {
public function index(): void {
$this->requireLogin();
$this->render('dashboard/index');
}
}