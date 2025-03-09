<?php
/**
 * Simple JWT implementation using only vanilla PHP
 */
class JWT {
    private static $secret = 'your_secure_random_key_change_this_in_production';
    
    /**
     * Generate a JWT token
     */
    public static function generate($payload, $expiry = 3600) {
        // Create token parts
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload['iat'] = time(); // Issued at time
        $payload['exp'] = time() + $expiry; // Expiration time
        $payloadJson = json_encode($payload);
        
        // Base64 encode parts (with URL-safe characters)
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payloadJson));
        
        // Create signature
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, self::$secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }
    
    /**
     * Validate a JWT token
     */
    public static function validate($token) {
        // Split token parts
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, self::$secret, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($base64Signature !== $expectedSignature) {
            return false;
        }
        
        // Verify expiration
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
}
