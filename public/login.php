<?php
require_once __DIR__ . '/../app/Auth.php';

// If already authenticated, redirect to dashboard
if (Auth::isAuthenticated()) {
    header('Location: /');
    exit;
}

// Display login page
require_once __DIR__ . '/../resources/views/login.php';
