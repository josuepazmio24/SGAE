<?php
session_start();
function require_login() {
    if (empty($_SESSION['usuario'])) {
        header("Location: login.php");
        exit;
    }
}
