<?php

namespace Core;

class Response
{
    /**
     * Send JSON response with proper headers
     * 
     * @param mixed $data
     * @param int $statusCode
     * @return void
     */
    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send success JSON response
     * 
     * @param array $data
     * @param string $message
     * @return void
     */
    public static function success($data = [], $message = 'Success')
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Send error JSON response
     * 
     * @param string $error
     * @param int $statusCode
     * @param array $details
     * @return void
     */
    public static function error($error, $statusCode = 400, $details = [])
    {
        self::json([
            'success' => false,
            'error' => $error,
            'details' => $details
        ], $statusCode);
    }

    /**
     * Send validation error response
     * 
     * @param array $errors
     * @return void
     */
    public static function validationError($errors)
    {
        self::json([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }

    /**
     * Send not found response
     * 
     * @param string $resource
     * @return void
     */
    public static function notFound($resource = 'Resource')
    {
        self::json([
            'success' => false,
            'error' => $resource . ' not found'
        ], 404);
    }

    /**
     * Send internal server error response
     * 
     * @param string $error
     * @return void
     */
    public static function serverError($error = 'Internal server error')
    {
        self::json([
            'success' => false,
            'error' => $error
        ], 500);
    }
}