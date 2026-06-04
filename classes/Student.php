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
        
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
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
                $this->roomID = $user['allocatedRoomID'];
                
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
        
        $checkSql = "SELECT * FROM students WHERE regNumber = '$regNumber'";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            return false;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql1 = "INSERT INTO users (username, password, name, email, phone, role) 
                 VALUES ('$regNumber', '$hashedPassword', '$name', '', '', 'student')";
        
        if ($this->db->query($sql1)) {
            $userID = $this->db->getInsertId();
            
            $sql2 = "INSERT INTO students (userID, regNumber, program, year, applicationStatus, gender) 
                     VALUES ($userID, '$regNumber', '', 0, 'pending', '')";
            
            return $this->db->query($sql2);
        }
        return false;
    }
    
    public function saveStudentDetails($data) {
        $program = $this->db->escape($data['program']);
        $year = (int)$data['year'];
        $gender = $this->db->escape($data['gender']);
        $address = $this->db->escape($data['address']);
        $medical_condition = $this->db->escape($data['medical_condition']);
        $medical_condition_details = $this->db->escape($data['medical_condition_details']);
        $guardian_name = $this->db->escape($data['guardian_name']);
        $guardian_relationship = $this->db->escape($data['guardian_relationship']);
        $guardian_phone = $this->db->escape($data['guardian_phone']);
        
        $sql = "UPDATE students SET 
                    program = '$program',
                    year = $year,
                    gender = '$gender',
                    address = '$address',
                    medical_condition = '$medical_condition',
                    medical_condition_details = '$medical_condition_details',
                    guardian_name = '$guardian_name',
                    guardian_relationship = '$guardian_relationship',
                    guardian_phone = '$guardian_phone',
                    agreement_confirmed = 1,
                    applicationStatus = 'pending'
                WHERE studentID = {$this->studentID}";
        
        return $this->db->query($sql);
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

    public function loadByStudentID($studentID) {
        $studentID = (int)$studentID;
        $sql = "SELECT s.*, u.name, u.email, u.phone, u.password \
                FROM students s \
                JOIN users u ON s.userID = u.userID \
                WHERE s.studentID = $studentID";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
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
            $this->roomID = $user['allocatedRoomID'];

            return true;
        }
        return false;
    }

    public function loadByUserID($userID) {
        $userID = (int)$userID;
        $sql = "SELECT studentID FROM students WHERE userID = $userID";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            return $this->loadByStudentID($row['studentID']);
        }
        return false;
    }
    
    public function requestClearance() {
        $studentID = (int)$this->studentID;
        if ($studentID <= 0) {
            return false;
        }
        $sql = "INSERT INTO clearance (studentID, requestDate, status) 
                VALUES ($studentID, CURDATE(), 'pending')";
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