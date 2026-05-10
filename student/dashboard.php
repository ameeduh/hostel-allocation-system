<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];

$sql = "SELECT s.*, u.name, u.phone, u.email 
        FROM students s 
        JOIN users u ON s.userID = u.userID 
        WHERE s.studentID = $studentID";
$result = $db->query($sql);
$studentData = $result->fetch_assoc();

$hasDetails = ($studentData['year'] > 0 && $studentData['gender'] != '' && $studentData['guardian_name'] != '');

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_details'])) {
    $year = $_POST['year'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $medical_condition = $_POST['medical_condition'];
    $medical_condition_details = ($medical_condition == 'yes') ? $_POST['medical_condition_details'] : '';
    $guardian_name = $_POST['guardian_name'];
    $guardian_relationship = $_POST['guardian_relationship'];
    $guardian_phone = $_POST['guardian_phone'];
    $agreement = isset($_POST['agreement']) ? 1 : 0;
    
    // Validate agreement
    if($agreement != 1) {
        header("Location: dashboard.php?error=1");
        exit();
    }
    
    $sql1 = "UPDATE students SET 
                year = '$year', 
                gender = '$gender', 
                dob = '$dob', 
                address = '$address',
                medical_condition = '$medical_condition',
                medical_condition_details = '$medical_condition_details',
                guardian_name = '$guardian_name',
                guardian_relationship = '$guardian_relationship',
                guardian_phone = '$guardian_phone',
                agreement_confirmed = 1,
                applicationStatus = 'pending'
            WHERE studentID = $studentID";
    $db->query($sql1);
    
    $sql2 = "UPDATE users SET phone = '$phone', email = '$email' WHERE userID = {$_SESSION['user_id']}";
    $db->query($sql2);
    
    header("Location: dashboard.php?success=1");
    exit();
}

$showForm = isset($_GET['show_form']);
$success = isset($_GET['success']);
$error = isset($_GET['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=8">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="logo">
                <h2>Hostel</h2>
                <p>Allocation System</p>
            </div>
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-link">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="top-header">
                <h1>Welcome, <?php echo $studentData['name']; ?></h1>
                <p>Reg: <?php echo $_SESSION['username']; ?></p>
            </div>

            <?php if($success): ?>
                <div class="success-message">Details saved successfully!</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error-message">Please confirm that all information is correct.</div>
            <?php endif; ?>

            <?php if(!$hasDetails && !$showForm): ?>
                <div class="action-card" onclick="window.location.href='?show_form=1'">
                    <h3>Complete Your Details</h3>
                    <p>Fill in your personal information</p>
                </div>
            <?php endif; ?>

            <?php if($hasDetails): ?>
                <div class="action-card" onclick="window.location.href='approval_status.php'">
                    <h3>Approval Status</h3>
                </div>
                <div class="action-card" onclick="window.location.href='room_details.php'">
                    <h3>Room Details</h3>
                </div>
            <?php endif; ?>

            <?php if($showForm): ?>
                <div class="content-card">
                    <h2>Complete Your Details</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Year of Study</label>
                                <select name="year" required>
                                    <option value="">Select Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Home Address</label>
                            <textarea name="address" rows="2" required></textarea>
                        </div>

                        <h3>Medical Information</h3>
                        <div class="form-group">
                            <label>Any medical conditions?</label>
                            <select name="medical_condition" id="medical_condition" onchange="toggleMedical()">
                                <option value="no">No</option>
                                <option value="yes">Yes</option>
                            </select>
                        </div>
                        <div id="medical_div" style="display:none;">
                            <div class="form-group">
                                <label>Please specify</label>
                                <textarea name="medical_condition_details" rows="2" placeholder="e.g., Asthma, Diabetes, Allergies"></textarea>
                            </div>
                        </div>

                        <h3>Emergency Contact</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Guardian Name</label>
                                <input type="text" name="guardian_name" required>
                            </div>
                            <div class="form-group">
                                <label>Relationship</label>
                                <select name="guardian_relationship" required>
                                    <option value="">Select</option>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Brother">Brother</option>
                                    <option value="Sister">Sister</option>
                                    <option value="Guardian">Guardian</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Guardian Phone</label>
                            <input type="tel" name="guardian_phone" required>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" name="agreement" required>
                            <label>I confirm that all information provided is correct.</label>
                        </div>

                        <button type="submit" name="save_details" class="submit-btn">Submit Application</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleMedical() {
            var select = document.getElementById('medical_condition');
            var div = document.getElementById('medical_div');
            div.style.display = select.value == 'yes' ? 'block' : 'none';
        }
    </script>
</body>
</html>