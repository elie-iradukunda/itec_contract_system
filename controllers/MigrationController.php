<?php

namespace Controllers;

use Core\Controller;
use Core\Database;

class MigrationController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function run()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $migrationFiles = glob(__DIR__ . '/../database/migrations/*.php');
        $migrationOrder = [
            'CreateUsersTable' => 10,
            'CreateClientsTable' => 20,
            'CreateContractsTable' => 30,
            'CreateContractSignaturesTable' => 40,
            'CreateAuditLogsTable' => 50,
            'CreateContractSealsTable' => 60,
            'CreateContractVersionsTable' => 70,
            'CreateDocVersionsTable' => 75,
            'CreateDocTrackedChangesTable' => 76,
            'CreateDocSignaturesTable' => 77,
            'CreateDocSigningOrderTable' => 78,
            'CreateDocSignatureAuditTable' => 79,
            'CreateDocDistributionsTable' => 80,
            'CreateDocumentHashesTable' => 85,
            'CreateNotificationsTable' => 90,
            'CreateUploadedDocumentsTable' => 100,
        ];

        usort($migrationFiles, function ($a, $b) use ($migrationOrder) {
            $aName = basename($a, '.php');
            $bName = basename($b, '.php');
            $aOrder = $migrationOrder[$aName] ?? 999;
            $bOrder = $migrationOrder[$bName] ?? 999;

            if ($aOrder === $bOrder) {
                return strcmp($aName, $bName);
            }

            return $aOrder <=> $bOrder;
        });

        $executed = $this->db->query("SELECT migration FROM migrations")->fetchAll(\PDO::FETCH_COLUMN);
        
        echo "<pre>";
        echo "========================================\n";
        echo "Running Migrations\n";
        echo "========================================\n\n";
        
        $count = 0;
        
        foreach ($migrationFiles as $file) {
            $name = basename($file, '.php');
            
            if (in_array($name, $executed)) {
                echo "SKIP: {$name} (already executed)\n";
                continue;
            }
            
            require_once $file;
            $migration = new $name();
            $migration->up();
            
            $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$name]);
            
            echo "RUN: {$name}\n";
            $count++;
        }
        
        echo "\n========================================\n";
        echo "Completed: {$count} migration(s) executed\n";
        echo "========================================\n";
        echo "</pre>";
        
        if ($count === 0) {
            echo "<p>All migrations are already up to date!</p>";
        } else {
            echo "<p>Database schema updated successfully!</p>";
        }
        
        echo "<br><a href='/itec_contract_system/'>Back to Home</a>";
    }

    public function rollback()
    {
        $lastMigration = $this->db->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 1")->fetch(\PDO::FETCH_COLUMN);
        
        if ($lastMigration) {
            require_once __DIR__ . "/../database/migrations/{$lastMigration}.php";
            $migration = new $lastMigration();
            $migration->down();
            
            $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration = ?");
            $stmt->execute([$lastMigration]);
            
            echo "Rolled back: {$lastMigration}";
        } else {
            echo "No migrations to rollback";
        }
        
        echo "<br><a href='/itec_contract_system/migrate'>Back to Migrations</a>";
    }
}
