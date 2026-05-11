<?php

namespace Core;

use PDOException;
use Exception;

class DatabaseErrorHandler
{
    /**
     * Execute database operation with error handling
     * 
     * @param callable $operation
     * @param string $errorMessage
     * @return array
     */
    public static function execute($operation, $errorMessage = 'Database operation failed')
    {
        try {
            return [
                'success' => true,
                'data' => $operation()
            ];
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            
            // Handle specific MySQL errors
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;
            
            if ($sqlState === '42S02') {
                $error = 'Table not found. Run migrations first.';
            } elseif ($sqlState === '42S22') {
                $error = 'Column not found. Check your migration.';
            } elseif ($driverCode === 1062) {
                $error = 'Duplicate entry. Record already exists.';
            } elseif ($driverCode === 1451) {
                $error = 'Cannot delete - record is referenced elsewhere.';
            } elseif ($driverCode === 1452) {
                $error = 'Foreign key constraint failed.';
            } else {
                $error = $errorMessage . ': ' . $e->getMessage();
            }
            
            return [
                'success' => false,
                'error' => $error,
                'sql_state' => $sqlState,
                'driver_code' => $driverCode
            ];
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $errorMessage . ': ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute query with automatic error handling
     * 
     * @param \PDO $db
     * @param string $sql
     * @param array $params
     * @param string $errorMessage
     * @return array
     */
    public static function query($db, $sql, $params = [], $errorMessage = 'Query failed')
    {
        return self::execute(function() use ($db, $sql, $params) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }, $errorMessage);
    }

    /**
     * Execute fetch all query
     * 
     * @param \PDO $db
     * @param string $sql
     * @param array $params
     * @param string $errorMessage
     * @return array
     */
    public static function fetchAll($db, $sql, $params = [], $errorMessage = 'Query failed')
    {
        $result = self::query($db, $sql, $params, $errorMessage);
        
        if ($result['success']) {
            return [
                'success' => true,
                'data' => $result['data']->fetchAll()
            ];
        }
        
        return $result;
    }

    /**
     * Execute fetch one query
     * 
     * @param \PDO $db
     * @param string $sql
     * @param array $params
     * @param string $errorMessage
     * @return array
     */
    public static function fetchOne($db, $sql, $params = [], $errorMessage = 'Query failed')
    {
        $result = self::query($db, $sql, $params, $errorMessage);
        
        if ($result['success']) {
            return [
                'success' => true,
                'data' => $result['data']->fetch()
            ];
        }
        
        return $result;
    }
}