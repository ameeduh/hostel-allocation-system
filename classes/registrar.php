<?php
require_once 'User.php';

class Registrar extends User {
    private $registrarID;
    private $office;
    
    public function login($username, $password) {
        $username = $this->db->escape($username);
        
        $sql = "SELECT r.*, u.name, u.email, u.phone, u.password 
                FROM registrars r 
                JOIN users u ON r.userID = u.userID 
                WHERE u.username = '$username'";
        
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check plain text password first
            if ($password == $user['password']) {
                $this->userID = $user['userID'];
                $this->username = $user['username'];
                $this->name = $user['name'];
                $this->email = $user['email'];
                $this->phone = $user['phone'];
                $this->role = 'registrar';
                $this->registrarID = $user['registrarID'];
                $this->office = $user['office'];
                
                $_SESSION['user_id'] = $this->userID;
                $_SESSION['username'] = $this->username;
                $_SESSION['name'] = $this->name;
                $_SESSION['role'] = $this->role;
                
                return true;
            }
        }
        return false;
    }
    
    public function getRegistrarID() { return $this->registrarID; }
    public function getOffice() { return $this->office; }
}
?>