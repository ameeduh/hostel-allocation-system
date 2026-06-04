<?php
require_once 'User.php';

class Admin extends User {
    private $adminID;
    private $permissions;
    
    public function login($username, $password) {
        $username = $this->db->escape($username);
        
        $sql = "SELECT a.*, u.name, u.email, u.phone, u.password 
                FROM admins a 
                JOIN users u ON a.userID = u.userID 
                WHERE u.username = '$username' AND u.role = 'admin'";
        
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $this->userID = $user['userID'];
                $this->username = $user['username'];
                $this->name = $user['name'];
                $this->email = $user['email'];
                $this->phone = $user['phone'];
                $this->role = 'admin';
                $this->adminID = $user['adminID'];
                $this->permissions = $user['permissions'];
                
                $_SESSION['user_id'] = $this->userID;
                $_SESSION['username'] = $this->username;
                $_SESSION['name'] = $this->name;
                $_SESSION['role'] = $this->role;
                
                return true;
            }
        }
        return false;
    }
    
    public function addAccountant($data) {
        $hashedPassword = password_hash('default123', PASSWORD_DEFAULT);
        
        $username = $this->db->escape($data['username']);
        $name = $this->db->escape($data['name']);
        $email = $this->db->escape($data['email']);
        $phone = $this->db->escape($data['phone']);
        $department = $this->db->escape($data['department']);
        
        $sql1 = "INSERT INTO users (username, password, name, email, phone, role) 
                 VALUES ('$username', '$hashedPassword', '$name', '$email', '$phone', 'accounts')";
        
        if ($this->db->query($sql1)) {
            $userID = $this->db->getInsertId();
            
            $sql2 = "INSERT INTO accountants (userID, department) VALUES ($userID, '$department')";
            return $this->db->query($sql2);
        }
        return false;
    }
    
    public function addWarden($data) {
        $hashedPassword = password_hash('default123', PASSWORD_DEFAULT);
        
        $username = $this->db->escape($data['username']);
        $name = $this->db->escape($data['name']);
        $email = $this->db->escape($data['email']);
        $phone = $this->db->escape($data['phone']);
        $hostelAssigned = $this->db->escape($data['hostelAssigned']);
        
        $sql1 = "INSERT INTO users (username, password, name, email, phone, role) 
                 VALUES ('$username', '$hashedPassword', '$name', '$email', '$phone', 'warden')";
        
        if ($this->db->query($sql1)) {
            $userID = $this->db->getInsertId();
            
            $sql2 = "INSERT INTO wardens (userID, hostelAssigned) VALUES ($userID, '$hostelAssigned')";
            return $this->db->query($sql2);
        }
        return false;
    }
    
    public function viewAllUsers() {
        $sql = "SELECT userID, username, name, email, phone, role FROM users ORDER BY role, name";
        $result = $this->db->query($sql);
        $users = array();
        if($result) {
            while($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        return $users;
    }
    
    public function deleteUser($userID) {
        if ($userID == $this->userID) {
            return false;
        }
        
        $sql = "DELETE FROM users WHERE userID = $userID";
        return $this->db->query($sql);
    }
    
    public function addRoom($data) {
        $roomNumber = $this->db->escape($data['roomNumber']);
        $hostelName = $this->db->escape($data['hostelName']);
        $gender = $this->db->escape($data['gender']);
        $capacity = (int)$data['capacity'];
        $availableBeds = $capacity;
        
        $sql = "INSERT INTO rooms (roomNumber, hostelName, gender, capacity, availableBeds, status) 
                VALUES ('$roomNumber', '$hostelName', '$gender', $capacity, $availableBeds, 'available')";
        return $this->db->query($sql);
    }
    
    public function viewAllRooms() {
        $sql = "SELECT * FROM rooms ORDER BY hostelName, roomNumber";
        $result = $this->db->query($sql);
        $rooms = array();
        if($result) {
            while($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
        }
        return $rooms;
    }
    
    public function deleteRoom($roomID) {
        $checkSql = "SELECT COUNT(*) as count FROM students WHERE allocatedRoomID = $roomID AND allocationStatus = 'active'";
        $checkResult = $this->db->query($checkSql);
        $check = $checkResult->fetch_assoc();
        
        if ($check['count'] > 0) {
            return false;
        }
        
        $sql = "DELETE FROM rooms WHERE roomID = $roomID";
        return $this->db->query($sql);
    }
    
    public function viewAllStudentsReport() {
        $sql = "SELECT s.*, u.name FROM students s JOIN users u ON s.userID = u.userID ORDER BY u.name";
        $result = $this->db->query($sql);
        $students = array();
        if($result) {
            while($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
        }
        return $students;
    }
    
    public function viewDashboard() {
        $data = [];
        
        $sql1 = "SELECT COUNT(*) as totalStudents FROM students";
        $result = $this->db->query($sql1);
        $data['totalStudents'] = $result->fetch_assoc()['totalStudents'];
        
        $sql2 = "SELECT COUNT(*) as totalRooms FROM rooms";
        $result = $this->db->query($sql2);
        $data['totalRooms'] = $result->fetch_assoc()['totalRooms'];
        
        $sql3 = "SELECT COUNT(*) as allocatedStudents FROM students WHERE applicationStatus = 'allocated'";
        $result = $this->db->query($sql3);
        $data['allocatedStudents'] = $result->fetch_assoc()['allocatedStudents'];
        
        $sql4 = "SELECT COUNT(*) as pendingApprovals FROM students WHERE applicationStatus = 'pending'";
        $result = $this->db->query($sql4);
        $data['pendingApprovals'] = $result->fetch_assoc()['pendingApprovals'];
        
        $sql5 = "SELECT SUM(capacity) as totalBeds, SUM(availableBeds) as availableBeds FROM rooms";
        $result = $this->db->query($sql5);
        $beds = $result->fetch_assoc();
        $data['totalBeds'] = $beds['totalBeds'] ?? 0;
        $data['availableBeds'] = $beds['availableBeds'] ?? 0;
        
        return $data;
    }
    
    public function getAdminID() { return $this->adminID; }
    public function getPermissions() { return $this->permissions; }
}
?>