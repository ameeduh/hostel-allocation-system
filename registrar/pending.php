<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['approve'])) {
        $studentID = $_POST['studentID'];
        $disciplinary = isset($_POST['disciplinary']) ? $_POST['disciplinary'] : 'no';
        $commitment = isset($_POST['commitment']) ? $_POST['commitment'] : 'no';
        $medical = isset($_POST['medical']) ? $_POST['medical'] : 'no';
        $hostel_conduct = isset($_POST['hostel_conduct']) ? $_POST['hostel_conduct'] : 'no';
        
        $sql = "UPDATE students SET 
                    registrar_status = 'approved',
                    disciplinary = '$disciplinary',
                    commitment_registrar = '$commitment',
                    medical_condition_registrar = '$medical',
                    hostel_conduct = '$hostel_conduct'
                WHERE studentID = $studentID";
        $db->query($sql);
        header("Location: pending.php?approved=1");
        exit();
    }
    if(isset($_POST['reject'])) {
        $studentID = $_POST['studentID'];
        $reason = $_POST['reason'];
        $sql = "UPDATE students SET registrar_status = 'rejected', registrar_reason = '$reason' WHERE studentID = $studentID";
        $db->query($sql);
        header("Location: pending.php?rejected=1");
        exit();
    }
}

// Only show students who have completed details (applicationStatus = 'pending')
$sql = "SELECT s.studentID, s.regNumber, s.program, s.year, u.name 
        FROM students s 
        JOIN users u ON s.userID = u.userID 
        WHERE (s.registrar_status IS NULL OR s.registrar_status = 'pending')
        AND s.applicationStatus = 'pending'
        ORDER BY s.studentID";
$result = $db->query($sql);
$pendingStudents = $result->fetch_all(MYSQLI_ASSOC);

$selectedStudentID = isset($_GET['review']) ? (int)$_GET['review'] : null;
$selectedStudent = null;

if($selectedStudentID) {
    $sql = "SELECT s.*, u.name 
            FROM students s 
            JOIN users u ON s.userID = u.userID 
            WHERE s.studentID = $selectedStudentID";
    $result = $db->query($sql);
    $selectedStudent = $result->fetch_assoc();
}

$approved = isset($_GET['approved']);
$rejected = isset($_GET['rejected']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Eligibility - Registrar Portal</title>
    <link rel="stylesheet" href="../css/style.css?v=23">
    <style>
        .full-page-container {
            width: 100%;
            min-height: 100vh;
            background-color: #F5F5F5;
            padding: 40px;
        }
        .full-content-card {
            background-color: white;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            border-radius: 10px;
            border-top: 5px solid #FFD700;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .student-list {
            margin-bottom: 30px;
        }
        .student-list h2 {
            color: #8B4513;
            margin-bottom: 15px;
        }
        .student-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .student-btn {
            background-color: #FFF8DC;
            border: 1px solid #FFD700;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        .student-btn:hover {
            background-color: #FFD700;
        }
        .student-btn.active {
            background-color: #8B4513;
            color: white;
            border-color: #8B4513;
        }
        .review-section {
            background-color: #FFF8DC;
            border: 1px solid #FFD700;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .student-header {
            border-bottom: 1px solid #FFD700;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .student-name {
            font-size: 20px;
            font-weight: bold;
            color: #8B4513;
        }
        .student-reg {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .eligibility-question {
            margin: 15px 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .question-text {
            width: 320px;
            font-weight: 500;
            color: #333;
        }
        .radio-group {
            display: flex;
            gap: 20px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        .radio-group input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .btn-approve {
            background-color: #8B4513;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
            margin-right: 10px;
        }
        .btn-reject {
            background-color: #000000;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
        }
        .reject-reason {
            margin-top: 10px;
            padding: 8px;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: none;
        }
        .back-link-bottom {
            margin-top: 30px;
            text-align: right;
        }
        .back-btn {
            background-color: #8B4513;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        h1 {
            color: #8B4513;
            margin-bottom: 10px;
        }
        .no-students {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Student Eligibility Review</h1>

            <?php if($approved): ?>
                <div class="success-message">Student approved successfully!</div>
            <?php endif; ?>
            <?php if($rejected): ?>
                <div class="success-message">Student rejected successfully!</div>
            <?php endif; ?>

            <div class="student-list">
                <h2>Pending Students</h2>
                <div class="student-buttons">
                    <?php foreach($pendingStudents as $student): ?>
                        <a href="?review=<?php echo $student['studentID']; ?>" 
                           class="student-btn <?php echo ($selectedStudentID == $student['studentID']) ? 'active' : ''; ?>">
                            <?php echo $student['name']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if($selectedStudent): ?>
                <div class="review-section">
                    <div class="student-header">
                        <div class="student-name"><?php echo $selectedStudent['name']; ?></div>
                        <div class="student-reg">Registration Number: <?php echo $selectedStudent['regNumber']; ?></div>
                        <div class="student-reg">Program: <?php echo $selectedStudent['program'] ?: 'Not yet filled'; ?></div>
                        <div class="student-reg">Year: <?php echo $selectedStudent['year'] ?: 'Not yet filled'; ?></div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="studentID" value="<?php echo $selectedStudent['studentID']; ?>">
                        
                        <div class="eligibility-question">
                            <span class="question-text">1. Does the student have any disciplinary hearing?</span>
                            <div class="radio-group">
                                <label><input type="radio" name="disciplinary" value="yes"> Yes</label>
                                <label><input type="radio" name="disciplinary" value="no" checked> No</label>
                            </div>
                        </div>

                        <div class="eligibility-question">
                            <span class="question-text">2. Does the student have any commitment with Registrar's office?</span>
                            <div class="radio-group">
                                <label><input type="radio" name="commitment" value="yes"> Yes</label>
                                <label><input type="radio" name="commitment" value="no" checked> No</label>
                            </div>
                        </div>

                        <div class="eligibility-question">
                            <span class="question-text">3. Does the student have any medical condition requiring special room?</span>
                            <div class="radio-group">
                                <label><input type="radio" name="medical" value="yes"> Yes</label>
                                <label><input type="radio" name="medical" value="no" checked> No</label>
                            </div>
                        </div>

                        <div class="eligibility-question">
                            <span class="question-text">4. Does the student have any previous hostel conduct violation?</span>
                            <div class="radio-group">
                                <label><input type="radio" name="hostel_conduct" value="yes"> Yes</label>
                                <label><input type="radio" name="hostel_conduct" value="no" checked> No</label>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" name="approve" class="btn-approve" onclick="return confirm('Approve this student?')">✓ Approve Student</button>
                            <button type="button" class="btn-reject" onclick="toggleReject(this)">✗ Reject Student</button>
                            <input type="text" name="reason" class="reject-reason" placeholder="Enter rejection reason...">
                        </div>
                    </form>
                </div>
            <?php elseif(count($pendingStudents) > 0): ?>
                <div class="no-students">
                    <p>Click on a student name above to review their eligibility</p>
                </div>
            <?php else: ?>
                <div class="no-students">
                    <p>No pending students. Students must complete their details form first.</p>
                </div>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        function toggleReject(button) {
            var reasonInput = button.nextElementSibling;
            var form = button.closest('form');
            
            if(reasonInput.style.display === 'none' || reasonInput.style.display === '') {
                reasonInput.style.display = 'block';
                button.textContent = 'Confirm Reject';
                button.onclick = function() {
                    if(reasonInput.value.trim() === '') {
                        alert('Please enter a rejection reason');
                        return;
                    }
                    if(confirm('Reject this student?')) {
                        var hiddenSubmit = document.createElement('input');
                        hiddenSubmit.type = 'hidden';
                        hiddenSubmit.name = 'reject';
                        hiddenSubmit.value = '1';
                        form.appendChild(hiddenSubmit);
                        form.submit();
                    }
                };
            }
        }
    </script>
</body>
</html>