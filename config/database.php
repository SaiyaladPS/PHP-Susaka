<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $db = "not_db";
    
    private function __construct() {
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->db);
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Legacy support - keep $conn for backward compatibility
$conn = Database::getInstance()->getConnection();
?>