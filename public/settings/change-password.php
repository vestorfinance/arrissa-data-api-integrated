<?php
require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($newPassword === $confirmPassword && !empty($newPassword)) {
        if (Auth::changePassword($currentPassword, $newPassword)) {
            header('Location: /settings?success=password');
            exit;
        }
    }
    
    header('Location: /settings?error=password');
    exit;
}

header('Location: /settings');
exit;
