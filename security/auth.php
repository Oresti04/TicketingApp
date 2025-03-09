<?php
require_once 'jwt.php';
require_once 'security.php';

/**
 * Authenticate user using JWT token or session
 */
function authenticate() {
    // Check session first
    if (isset($_SESSION['user_id'])) {
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? 'user'
        ];
    }
    
    // Check for JWT token in cookies
    if (isset($_COOKIE['auth_token'])) {
        $payload = JWT::validate($_COOKIE['auth_token']);
        if ($payload) {
            // Refresh session data
            $_SESSION['user_id'] = $payload['user_id'];
            $_SESSION['username'] = $payload['username'];
            $_SESSION['role'] = $payload['role'];
            
            return $payload;
        }
    }
    
    // Check for Bearer token in Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $payload = JWT::validate($token);
        if ($payload) {
            return $payload;
        }
    }
    
    return null;
}

/**
 * Require authentication
 */
function requireAuth() {
    $user = authenticate();
    
    if (!$user) {
        // For AJAX/API requests
        if (isAPIRequest()) {
            header('HTTP/1.0 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        } 
        // For regular web requests
        else {
            header('Location: login.php');
            exit;
        }
    }
    
    return $user;
}

/**
 * Require admin role
 */
function requireAdmin() {
    $user = requireAuth();
    
    if ($user['role'] !== 'admin') {
        if (isAPIRequest()) {
            header('HTTP/1.0 403 Forbidden');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Admin access required']);
            exit;
        } else {
            header('Location: dashboard.php?error=access_denied');
            exit;
        }
    }
    
    return $user;
}

/**
 * Check if request is API request
 */
function isAPIRequest() {
    return (
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
        isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    );
}
