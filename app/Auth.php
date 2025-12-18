<?php

require_once __DIR__ . '/Database.php';

class Auth {
    
    private static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function login($username, $password) {
        $db = Database::getInstance();
        
        $stmt = $db->query("SELECT * FROM users WHERE username = ?", [$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            self::startSession();
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['login_time'] = time();
            return true;
        }
        
        return false;
    }
    
    public static function logout() {
        self::startSession();
        session_destroy();
        header('Location: /login');
        exit;
    }
    
    public static function check() {
        self::startSession();
        
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $timeout = 2 * 60 * 60;
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
            self::logout();
        }
        
        return true;
    }
    
    public static function isAuthenticated() {
        self::startSession();
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }
    
    public static function getUser() {
        self::startSession();
        return $_SESSION['username'] ?? null;
    }
    
    public static function changePassword($currentPassword, $newPassword) {
        self::startSession();
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $db = Database::getInstance();
        $stmt = $db->query("SELECT password FROM users WHERE id = ?", [$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($currentPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $_SESSION['user_id']]);
            return true;
        }
        
        return false;
    }
}
