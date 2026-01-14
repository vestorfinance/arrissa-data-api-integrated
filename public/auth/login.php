<?php
require_once __DIR__ . '/../../app/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        header('Location: /login?error=required');
        exit;
    }
    
    // Attempt login
    if (Auth::login($username, $password)) {
        header('Location: /dashboard');
        exit;
    } else {
        header('Location: /login?error=invalid');
        exit;
    }
} else {
    header('Location: /login');
    exit;
}
