<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'hostelsystem';
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
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
    
    public function getInsertId() {
        return $this->connection->insert_id;
    }
    
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>