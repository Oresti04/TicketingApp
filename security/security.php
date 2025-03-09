<?php

/**
 * Security utility functions
 */
class Security {
    /**
     * Generate a CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }
    
    /**
     * Regenerate CSRF token
     */
    public static function regenerateCSRFToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Check password strength
     */
    public static function isStrongPassword($password) {
        // At least 8 characters with uppercase, lowercase, number, and special char
        if (strlen($password) < 8) {
            return false;
        }
        
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasLowercase = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);
        
        return ($hasUppercase && $hasLowercase && $hasNumber && $hasSpecial);
    }
    
    /**
     * Sanitize user input
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitizeInput($value);
            }
        } else {
            $input = trim($input);
            $input = stripslashes($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        return $input;
    }
    
    /**
     * Set security headers
     */
    public static function setSecurityHeaders() {
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self'");
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($pdo, $userId, $eventType, $description) {
        $stmt = $pdo->prepare("INSERT INTO security_log (user_id, event_type, description, ip_address, user_agent) 
                             VALUES (?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $userId,
            $eventType,
            $description,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Check login attempts (rate limiting)
     */
    public static function checkLoginAttempts($pdo, $username) {
        // Delete old attempts (older than 15 minutes)
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE timestamp < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute();
        
        // Count recent attempts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE username = ? AND success = 0 
                               AND timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$username]);
        $attempts = $stmt->fetchColumn();
        
        return $attempts < 5; // Allow up to 5 failed attempts
    }
    
    /**
     * Record login attempt
     */
    public static function recordLoginAttempt($pdo, $username, $success) {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)");
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR'], $success ? 1 : 0]);
    }
}
