<?php
require_once 'User.php';

class Student extends User {
    private $studentID;
    private $regNumber;
    private $program;
    private $year;
    private $applicationStatus;
    private $gender;
    private $roomID;
    
    public function login($username, $password) {
        $username = $this->db->escape($username);
        
        $sql = "SELECT s.*, u.name, u.email, u.phone, u.password 
                FROM students s 
                JOIN users u ON s.userID = u.userID 
                WHERE s.regNumber = '$username'";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows == 1) {
            $user = $result->fetch_assoc();
            // Verify hashed password
            if (password_verify($password, $user['password'])) {
                $this->userID = $user['userID'];
                $this->username = $user['regNumber'];
                $this->name = $user['name'];
                $this->email = $user['email'];
                $this->phone = $user['phone'];
                $this->role = 'student';
                $this->studentID = $user['studentID'];
                $this->regNumber = $user['regNumber'];
                $this->program = $user['program'];
                $this->year = $user['year'];
                $this->applicationStatus = $user['applicationStatus'];
                $this->gender = $user['gender'];
                $this->roomID = $user['roomID'];
                
                $_SESSION['user_id'] = $this->userID;
                $_SESSION['username'] = $this->username;
                $_SESSION['name'] = $this->name;
                $_SESSION['role'] = $this->role;
                $_SESSION['studentID'] = $this->studentID;
                
                return true;
            }
        }
        return false;
    }
    
    public function register($regNumber, $name, $password) {
        $regNumber = $this->db->escape($regNumber);
        $name = $this->db->escape($name);
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if student already exists
        $checkSql = "SELECT * FROM students WHERE regNumber = '$regNumber'";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            return false; // Student already exists
        }
        
        // Insert into users table
        $sql1 = "INSERT INTO users (username, password, name, email, phone, role) 
                 VALUES ('$regNumber', '$hashedPassword', '$name', '', '', 'student')";
        
        if ($this->db->query($sql1)) {
            $userID = $this->db->getInsertId();
            
            // Insert into students table
            $sql2 = "INSERT INTO students (userID, regNumber, program, year, applicationStatus, gender) 
                     VALUES ($userID, '$regNumber', '', 0, 'pending', '')";
            
            return $this->db->query($sql2);
        }
        return false;
    }
    
    // After login, update student details (program, year, phone, gender)
    public function updateDetails($program, $year, $phone, $gender) {
        $program = $this->db->escape($program);
        $year = (int)$year;
        $phone = $this->db->escape($phone);
        $gender = $this->db->escape($gender);
        
        $sql = "UPDATE students SET program = '$program', year = $year, gender = '$gender' 
                WHERE studentID = {$this->studentID}";
        $this->db->query($sql);
        
        $sql2 = "UPDATE users SET phone = '$phone' WHERE userID = {$this->userID}";
        return $this->db->query($sql2);
    }
    
    public function viewStatus() {
        return $this->applicationStatus;
    }
    
    public function viewRoom() {
        if ($this->roomID) {
            $sql = "SELECT * FROM rooms WHERE roomID = {$this->roomID}";
            $result = $this->db->query($sql);
            return $result->fetch_assoc();
        }
        return null;
    }
    
    public function requestClearance() {
        $sql = "INSERT INTO clearance (studentID, requestDate, status) 
                VALUES ({$this->studentID}, CURDATE(), 'pending')";
        return $this->db->query($sql);
    }
    
    public function getStudentID() { return $this->studentID; }
    public function getRegNumber() { return $this->regNumber; }
    public function getProgram() { return $this->program; }
    public function getYear() { return $this->year; }
    public function getApplicationStatus() { return $this->applicationStatus; }
    public function getGender() { return $this->gender; }
    public function getRoomID() { return $this->roomID; }
}
?>