<?php
/**
 * API Response Helper
 * Standardized JSON response format untuk semua API endpoints
 */

header('Content-Type: application/json; charset=utf-8');

class APIResponse {
    
    /**
     * Success response
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    /**
     * Error response
     */
    public static function error($message = 'Error', $code = 400, $data = null) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    /**
     * Validation error
     */
    public static function validationError($errors = []) {
        http_response_code(422);
        echo json_encode([
            'status' => 'validation_error',
            'code' => 422,
            'message' => 'Validation failed',
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    /**
     * Unauthorized
     */
    public static function unauthorized($message = 'Unauthorized') {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'code' => 401,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    /**
     * Not found
     */
    public static function notFound($message = 'Resource not found') {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'code' => 404,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    /**
     * Server error
     */
    public static function serverError($message = 'Internal server error', $data = null) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'code' => 500,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// Enable CORS for development (disable in production)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');

?>