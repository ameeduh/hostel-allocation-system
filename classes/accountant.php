<?php
require_once 'User.php';

class Accountant extends User {
    private $accountantID;
    private $department;
    
    public function login($username, $password) {
        $username = $this->db->escape($username);
        
        $sql = "SELECT a.*, u.name, u.email, u.phone 
                FROM accountants a 
                JOIN users u ON a.userID = u.userID 
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
                $this->role = 'accounts';
                $this->accountantID = $user['accountantID'];
                $this->department = $user['department'];
                
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
                WHERE s.applicationStatus = 'pending'";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    public function verifyFees($studentID) {
        $sql = "UPDATE students SET applicationStatus = 'approved' WHERE studentID = $studentID";
        return $this->db->query($sql);
    }
    
    public function rejectStudent($studentID) {
        $sql = "UPDATE students SET applicationStatus = 'rejected' WHERE studentID = $studentID";
        return $this->db->query($sql);
    }
    
    public function viewVerifiedStudents() {
        $sql = "SELECT s.*, u.name 
                FROM students s 
                JOIN users u ON s.userID = u.userID 
                WHERE s.applicationStatus = 'approved'";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    public function getAccountantID() { return $this->accountantID; }
    public function getDepartment() { return $this->department; }
}
?>