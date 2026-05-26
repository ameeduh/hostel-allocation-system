<?php
// This file should be run daily via cron job or manually
// For Windows Task Scheduler or Linux Cron

require_once '../config/database.php';
require_once '../config/mail_config.php';

$db = new Database();

// Get all students with fee commitment that are not yet paid
$sql = "SELECT s.*, u.email, u.name 
        FROM students s 
        JOIN users u ON s.userID = u.userID 
        WHERE s.fee_commitment = 1 
        AND s.fee_commitment_status = 'pending'
        AND s.applicationStatus != 'rejected'";
$result = $db->query($sql);
$students = array();
if($result) {
    while($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

foreach($students as $student) {
    $expiryDate = $student['fee_commitment_expiry'];
    $today = date('Y-m-d');
    $daysRemaining = (strtotime($expiryDate) - strtotime($today)) / (60 * 60 * 24);
    
    // Week 2 (14 days remaining) - First reminder
    if($daysRemaining <= 14 && $daysRemaining > 7 && $student['fee_commitment_reminder1'] == 0) {
        $subject = "Fee Payment Reminder";
        $body = "<html><body>
                 <h2>Hostel Allocation System</h2>
                 <p>Dear " . $student['name'] . ",</p>
                 <p>This is a reminder that your fee commitment period ends in " . round($daysRemaining) . " days.</p>
                 <p><strong>Deadline:</strong> " . $expiryDate . "</p>
                 <p>Please make your payment before the deadline.</p>
                 </body></html>";
        sendEmail($student['email'], $subject, $body);
        
        $updateSql = "UPDATE students SET fee_commitment_reminder1 = 1 WHERE studentID = " . $student['studentID'];
        $db->query($updateSql);
    }
    
    // Week 3 (7 days remaining) - Second reminder
    if($daysRemaining <= 7 && $daysRemaining > 3 && $student['fee_commitment_reminder2'] == 0) {
        $subject = "Final Fee Payment Reminder";
        $body = "<html><body>
                 <h2>Hostel Allocation System</h2>
                 <p>Dear " . $student['name'] . ",</p>
                 <p>This is your FINAL REMINDER. Your fee commitment period ends in " . round($daysRemaining) . " days.</p>
                 <p><strong>Deadline:</strong> " . $expiryDate . "</p>
                 <p>Please make your payment immediately to avoid removal from the hostel.</p>
                 </body></html>";
        sendEmail($student['email'], $subject, $body);
        
        $updateSql = "UPDATE students SET fee_commitment_reminder2 = 1 WHERE studentID = " . $student['studentID'];
        $db->query($updateSql);
    }
    
    // Week 4 (3 days warning) - Warning email
    if($daysRemaining <= 3 && $daysRemaining > 0 && $student['fee_commitment_warning'] == 0) {
        $subject = "URGENT: Fee Payment Required";
        $body = "<html><body>
                 <h2>Hostel Allocation System</h2>
                 <p>Dear " . $student['name'] . ",</p>
                 <p><strong style='color:#c62828;'>URGENT:</strong> Your fee commitment period ends in " . round($daysRemaining) . " days.</p>
                 <p>If you do not pay within " . round($daysRemaining) . " days, you will be removed from the hostel.</p>
                 <p>Please contact the Accounts office immediately.</p>
                 </body></html>";
        sendEmail($student['email'], $subject, $body);
        
        $updateSql = "UPDATE students SET fee_commitment_warning = 1 WHERE studentID = " . $student['studentID'];
        $db->query($updateSql);
    }
    
    // Expired - Remove student from hostel
    if($daysRemaining <= 0) {
        // Get student's allocated room
        $roomSql = "SELECT allocatedRoomID FROM students WHERE studentID = " . $student['studentID'];
        $roomResult = $db->query($roomSql);
        $room = $roomResult->fetch_assoc();
        
        if($room && $room['allocatedRoomID']) {
            // Increase available beds in room
            $updateRoomSql = "UPDATE rooms SET availableBeds = availableBeds + 1 WHERE roomID = " . $room['allocatedRoomID'];
            $db->query($updateRoomSql);
            
            // Update room status if it was full
            $checkFullSql = "UPDATE rooms SET status = 'available' WHERE roomID = " . $room['allocatedRoomID'];
            $db->query($checkFullSql);
        }
        
        // Remove student from hostel
        $removeSql = "UPDATE students SET 
                      applicationStatus = 'rejected',
                      fee_commitment_status = 'rejected',
                      allocatedRoomID = NULL,
                      allocationStatus = 'inactive'
                      WHERE studentID = " . $student['studentID'];
        $db->query($removeSql);
        
        // Send removal notification to student
        $subject = "Hostel Allocation Removed";
        $body = "<html><body>
                 <h2>Hostel Allocation System</h2>
                 <p>Dear " . $student['name'] . ",</p>
                 <p><strong style='color:#c62828;'>Your hostel allocation has been REMOVED.</strong></p>
                 <p>Reason: Failure to pay fees within the 4-week commitment period.</p>
                 <p>Please contact the Accounts office for further assistance.</p>
                 </body></html>";
        sendEmail($student['email'], $subject, $body);
        
        // Notify Registrar
        $registrarSql = "SELECT email FROM users WHERE role = 'registrar' LIMIT 1";
        $registrarResult = $db->query($registrarSql);
        $registrar = $registrarResult->fetch_assoc();
        
        if($registrar) {
            $subject = "Student Removed - Fee Commitment Expired";
            $body = "<html><body>
                     <h2>Hostel Allocation System - Notification</h2>
                     <p>The following student has been removed from the hostel due to expired fee commitment:</p>
                     <p><strong>Student:</strong> " . $student['name'] . " (" . $student['regNumber'] . ")</p>
                     <p><strong>Expiry Date:</strong> " . $expiryDate . "</p>
                     </body></html>";
            sendEmail($registrar['email'], $subject, $body);
        }
    }
}

echo "Fee commitment check completed.\n";
?>