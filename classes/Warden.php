<?php
require_once 'User.php';

class Warden extends User {
    private $wardenID;
    private $hostelAssigned;
    
    public function login($username, $password) {
        $username = $this->db->escape($username);
        
        $sql = "SELECT w.*, u.name, u.email, u.phone, u.password 
                FROM wardens w 
                JOIN users u ON w.userID = u.userID 
                WHERE u.username = '$username' AND u.role = 'warden'";
        
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $this->userID = $user['userID'];
                $this->username = $user['username'];
                $this->name = $user['name'];
                $this->email = $user['email'];
                $this->phone = $user['phone'];
                $this->role = 'warden';
                $this->wardenID = $user['wardenID'];
                $this->hostelAssigned = $user['hostelAssigned'];
                
                $_SESSION['user_id'] = $this->userID;
                $_SESSION['username'] = $this->username;
                $_SESSION['name'] = $this->name;
                $_SESSION['role'] = $this->role;
                
                return true;
            }
        }
        return false;
    }
    
    public function getWardenID() { return $this->wardenID; }
    public function getHostelAssigned() { return $this->hostelAssigned; }
}
?>