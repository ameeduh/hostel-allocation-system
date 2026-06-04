<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $clearanceID = (int)$_POST['clearanceID'];
    $action = $_POST['action'];
    
    if($action == 'approve') {
        // Get studentID from clearance request
        $getSql = "SELECT studentID FROM clearance WHERE clearanceID = $clearanceID";
        $getResult = $db->query($getSql);
        $clearance = $getResult->fetch_assoc();
        $studentID = $clearance['studentID'];
        
        // Get allocated room for this student
        $roomSql = "SELECT allocatedRoomID FROM students WHERE studentID = $studentID";
        $roomResult = $db->query($roomSql);
        $student = $roomResult->fetch_assoc();
        $roomID = $student['allocatedRoomID'];
        
        if($roomID) {
            // Update room - increase available beds
            $updateRoomSql = "UPDATE rooms SET availableBeds = availableBeds + 1 WHERE roomID = $roomID";
            $db->query($updateRoomSql);
            
            // Update room status if it was full
            $updateStatusSql = "UPDATE rooms SET status = 'available' WHERE roomID = $roomID";
            $db->query($updateStatusSql);
        }
        
        // Update student - vacate room
        $updateStudentSql = "UPDATE students SET 
                                allocationStatus = 'vacated', 
                                applicationStatus = 'cleared',
                                allocatedRoomID = NULL
                            WHERE studentID = $studentID";
        $db->query($updateStudentSql);
        
        // Update clearance status
        $updateClearanceSql = "UPDATE clearance SET status = 'approved', clearanceDate = CURDATE() WHERE clearanceID = $clearanceID";
        $db->query($updateClearanceSql);
        
        header("Location: dashboard.php?page=clearance&approved=1");
        exit();
    } 
    elseif($action == 'reject') {
        // Just update clearance status to rejected
        $updateClearanceSql = "UPDATE clearance SET status = 'rejected' WHERE clearanceID = $clearanceID";
        $db->query($updateClearanceSql);
        
        header("Location: dashboard.php?page=clearance&rejected=1");
        exit();
    }
}

header("Location: dashboard.php?page=clearance");
exit();
?>