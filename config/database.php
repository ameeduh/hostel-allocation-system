<?php
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "hostelsystem";
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->connection = new mysqli(
            $this->host, 
            $this->username, 
            $this->password, 
            $this->database
        );
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        // Set charset to prevent some injection attacks
        $this->connection->set_charset("utf8mb4");
    }
    
    // ADD THIS METHOD - for prepared statements
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    // For backward compatibility
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function getInsertId() {
        return $this->connection->insert_id;
    }
    
    public function close() {
        $this->connection->close();
    }
}
?>