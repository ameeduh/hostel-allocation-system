<?php
// Run this at the end of each semester (via cron job)
// This script expires all paid_students records from previous semester

require_once '../config/database.php';

$db = new Database();

// Get current semester
$currentSemester = date('n') <= 6 ? 'Semester 1' : 'Semester 2';
$currentYear = date('Y');

// Expire all records that are not from current semester
$updateSql = "UPDATE paid_students SET status = 'expired' 
              WHERE semester != '$currentSemester' 
              OR academic_year NOT LIKE '%$currentYear%'";

if($db->query($updateSql)) {
    echo "Expired old semester records: " . $db->getConnection()->affected_rows . " records updated.\n";
} else {
    echo "Error: " . $db->getConnection()->error . "\n";
}

// Also reset student statuses for expired students
$resetSql = "UPDATE students s 
             SET s.applicationStatus = 'pending', s.approved_source = NULL
             WHERE s.regNumber IN (SELECT regNumber FROM paid_students WHERE status = 'expired')";
$db->query($resetSql);

echo "Student statuses reset for expired records.\n";
?>