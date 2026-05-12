<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
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

$regNumber = $_SESSION['username'];
$detectedProgram = '';

if(strpos($regNumber, 'BscICT') !== false) {
    $detectedProgram = 'ICT';
} elseif(strpos($regNumber, 'BscNM') !== false) {
    $detectedProgram = 'Nursing';
} elseif(strpos($regNumber, 'BscBA') !== false) {
    $detectedProgram = 'Business Administration';
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_details'])) {
    $program = $detectedProgram;
    $year = $_POST['year'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $medical_condition = $_POST['medical_condition'];
    $medical_condition_details = ($medical_condition == 'yes') ? $_POST['medical_condition_details'] : '';
    $guardian_name = $_POST['guardian_name'];
    $guardian_relationship = $_POST['guardian_relationship'];
    $guardian_phone = $_POST['guardian_phone'];
    $agreement = isset($_POST['agreement']) ? 1 : 0;
    
    if($agreement != 1) {
        header("Location: details.php?error=1");
        exit();
    }
    
    $sql1 = "UPDATE students SET 
                program = '$program',
                year = '$year', 
                gender = '$gender', 
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
    
    header("Location: details.php?success=1");
    exit();
}

$success = isset($_GET['success']);
$error = isset($_GET['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Details - Student Portal</title>
    <link rel="stylesheet" href="../css/style.css?v=21">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F5F5F5;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .back-link {
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            background-color: #8B4513;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .back-btn:hover {
            background-color: #A0522D;
        }

        .form-card {
            background-color: white;
            border-radius: 10px;
            border-top: 5px solid #FFD700;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }

        h1 {
            color: #8B4513;
            margin-bottom: 25px;
            text-align: center;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #8B4513;
            margin-bottom: 5px;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #8B4513;
        }

        .form-group input[readonly] {
            background-color: #f5f5f5;
        }

        h3 {
            color: #8B4513;
            margin: 20px 0 15px 0;
            font-size: 18px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .checkbox-group input {
            width: auto;
        }

        .submit-btn {
            background-color: #8B4513;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #A0522D;
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="form-card">
            <h1>Complete Your Details</h1>

            <?php if($success): ?>
                <div class="success-message">Details saved successfully!</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error-message">Please confirm that all information is correct.</div>
            <?php endif; ?>

            <?php if(!$hasDetails): ?>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Program</label>
                            <input type="text" value="<?php echo $detectedProgram; ?>" readonly disabled>
                        </div>
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
            <?php else: ?>
                <div class="success-message">Your details have been submitted. Awaiting Accountant approval.</div>
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