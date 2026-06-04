<?php
require_once __DIR__ . '/../config/database.php';

class User {
    protected $db;
    protected $userID;
    protected $username;
    protected $name;
    protected $email;
    protected $phone;
    protected $role;
    protected $isLoggedIn = false;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function logout() {
        session_destroy();
        $this->isLoggedIn = false;
        return true;
    }
    
    public function getUserID() { return $this->userID; }
    public function getUsername() { return $this->username; }
    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getPhone() { return $this->phone; }
    public function getRole() { return $this->role; }
    public function isLoggedIn() { return $this->isLoggedIn; }
}
?>