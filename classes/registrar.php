<?php
require_once 'User.php';

class Registrar extends User {
    private $registrarID;
    private $office;
    
    public function login($username, $password) {
        $username = $this->db->escape($username);
        
        $sql = "SELECT r.*, u.name, u.email, u.phone 
                FROM registrars r 
                JOIN users u ON r.userID = u.userID 
                WHERE u.username = '$username'";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if ($password == 'password123') {
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
    
    public function viewPendingStudents() {
        $sql = "SELECT s.*, u.name 
                FROM students s 
                JOIN users u ON s.userID = u.userID 
                WHERE s.registrar_status IS NULL OR s.registrar_status = 'pending'
                ORDER BY s.studentID";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    public function approveStudent($studentID) {
        $sql = "UPDATE students SET registrar_status = 'approved' WHERE studentID = $studentID";
        return $this->db->query($sql);
    }
    
    public function rejectStudent($studentID, $reason) {
        $reason = $this->db->escape($reason);
        $sql = "UPDATE students SET registrar_status = 'rejected', registrar_reason = '$reason' WHERE studentID = $studentID";
        return $this->db->query($sql);
    }
    
    public function viewApprovedStudents() {
        $sql = "SELECT s.*, u.name 
                FROM students s 
                JOIN users u ON s.userID = u.userID 
                WHERE s.registrar_status = 'approved'
                ORDER BY s.studentID";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    public function getRegistrarID() { return $this->registrarID; }
    public function getOffice() { return $this->office; }
}
?>