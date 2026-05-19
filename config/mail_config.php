<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'C:\xampp\htdocs\HostelSystem\phpmailer\PHPMailer.php';
require_once 'C:\xampp\htdocs\HostelSystem\phpmailer\SMTP.php';
require_once 'C:\xampp\htdocs\HostelSystem\phpmailer\Exception.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings - YOU NEED TO UPDATE THESE
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ibuhidah@gmail.com';  // CHANGE THIS
        $mail->Password   = 'otbb zkvq wpna xjdb';     // CHANGE THIS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('ibuhidah@gmail.com', 'Hostel Allocation System');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>