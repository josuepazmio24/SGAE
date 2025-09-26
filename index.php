<?php
session_start();
if (!empty($_SESSION['usuario'])) {
    header('Location: public/dashboard.php'); exit;
} else {
    header('Location: public/login.php'); exit;
}
