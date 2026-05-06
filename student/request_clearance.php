<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

spl_autoload_register(function($class_name) {
    include '../classes/' . $class_name . '.php';
});

$student = new Student();
$student->login($_SESSION['username'], 'password123');

if($student->requestClearance()) {
    header("Location: dashboard.php?cleared=1");
} else {
    header("Location: dashboard.php?error=1");
}
exit();
?>