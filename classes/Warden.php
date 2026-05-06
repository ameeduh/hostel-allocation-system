<?php
require_once 'User.php';

class Warden extends User {
    private $wardenID;
    private $hostelAssigned;
    
    public function login($username, $password) {
        $username = $this->db->escape($username);
        
        $sql = "SELECT w.*, u.name, u.email, u.phone 
                FROM wardens w 
                JOIN users u ON w.userID = u.userID 
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
    
    public function viewApprovedStudents() {
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
    
    public function viewAvailableRooms() {
        $sql = "SELECT * FROM rooms WHERE availableBeds > 0 AND status = 'available'";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    public function allocateRoom($studentID, $roomID) {
    // Get student gender
    $studentSql = "SELECT gender FROM students WHERE studentID = $studentID";
    $studentResult = $this->db->query($studentSql);
    $student = $studentResult->fetch_assoc();
    
    // Check room gender matches student gender
    $roomSql = "SELECT gender, availableBeds FROM rooms WHERE roomID = $roomID";
    $roomResult = $this->db->query($roomSql);
    $room = $roomResult->fetch_assoc();
    
    if ($room['gender'] != $student['gender']) {
        return false; // Gender mismatch
    }
    
    if ($room['availableBeds'] > 0) {
        // Update room available beds
        $sql2 = "UPDATE rooms SET availableBeds = availableBeds - 1 WHERE roomID = $roomID";
        $this->db->query($sql2);
        
        // Update student with allocated room
        $sql3 = "UPDATE students SET 
                  applicationStatus = 'allocated', 
                  allocatedRoomID = $roomID, 
                  allocatedDate = CURDATE(), 
                  allocationStatus = 'active'
                  WHERE studentID = $studentID";
        $this->db->query($sql3);
        
        // Update room status if full
        $sql4 = "UPDATE rooms SET status = 'full' WHERE roomID = $roomID AND availableBeds = 0";
        $this->db->query($sql4);
        
        return true;
    }
    return false;
}
    
    public function viewAllocatedStudents() {
        $sql = "SELECT s.studentID, s.regNumber, u.name as studentName, 
                       s.allocatedRoomID, s.allocatedDate, s.allocationStatus,
                       r.roomNumber, r.hostelName
                FROM students s 
                JOIN users u ON s.userID = u.userID
                LEFT JOIN rooms r ON s.allocatedRoomID = r.roomID
                WHERE s.applicationStatus = 'allocated' AND s.allocationStatus = 'active'";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    public function vacateRoom($studentID) {
        // Get current allocated room
        $getSql = "SELECT allocatedRoomID FROM students WHERE studentID = $studentID";
        $result = $this->db->query($getSql);
        $student = $result->fetch_assoc();
        $roomID = $student['allocatedRoomID'];
        
        if ($roomID) {
            // Update student - vacate room
            $sql1 = "UPDATE students SET allocationStatus = 'vacated', applicationStatus = 'cleared' 
                     WHERE studentID = $studentID";
            $this->db->query($sql1);
            
            // Increase available beds in room
            $sql2 = "UPDATE rooms SET availableBeds = availableBeds + 1 WHERE roomID = $roomID";
            $this->db->query($sql2);
            
            // Update room status to available
            $sql3 = "UPDATE rooms SET status = 'available' WHERE roomID = $roomID";
            $this->db->query($sql3);
            
            return true;
        }
        return false;
    }
    
    public function processClearance($clearanceID, $action, $reason = '') {
        if ($action == 'approve') {
            $sql = "UPDATE clearance SET status = 'approved', clearanceDate = CURDATE() 
                    WHERE clearanceID = $clearanceID";
            $this->db->query($sql);
            
            $getSql = "SELECT studentID FROM clearance WHERE clearanceID = $clearanceID";
            $result = $this->db->query($getSql);
            $clearance = $result->fetch_assoc();
            
            // Vacate room when clearance is approved
            $this->vacateRoom($clearance['studentID']);
            
            return true;
        } else {
            $reason = $this->db->escape($reason);
            $sql = "UPDATE clearance SET status = 'rejected', reason = '$reason' 
                    WHERE clearanceID = $clearanceID";
            return $this->db->query($sql);
        }
    }
    
    public function getWardenID() { return $this->wardenID; }
    public function getHostelAssigned() { return $this->hostelAssigned; }
}
?>