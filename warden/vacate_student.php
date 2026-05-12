<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
require_once '../classes/Warden.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vacate'])) {
    $studentID = $_POST['studentID'];
    $return_page = isset($_POST['return_page']) ? $_POST['return_page'] : 'allocated.php';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    
    $warden = new Warden();
    $warden->login($_SESSION['username'], 'password123');
    
    if($warden->vacateRoom($studentID)) {
        header("Location: $return_page?vacated=1");
    } else {
        header("Location: $return_page?error=1");
    }
    exit();
}
?>