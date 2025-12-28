<?php
session_start();
require '../includes/db.php';

$message = '';
$message_type = ''; // 'success' or 'error'

// Only students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Fetch student info for faculty & study level
$stmt = $conn->prepare("SELECT faculty_id, study_level_id FROM users WHERE id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result()->fetch_assoc();
$faculty_id = $student_result['faculty_id'];
$study_level_id = $student_result['study_level_id'];

$level_map = [
    1 => "Diploma",
    2 => "Bachelor",
    3 => "Master"
];

// Convert numeric ID to string to match offered_subjects table
$study_level = $level_map[$study_level_id];


$message = '';

// --- Handle Add (register subject) ---
if (isset($_GET['add'])) {
    $offered_id = intval($_GET['add']);

    // Get the added_date of the subject
    $stmt = $conn->prepare("SELECT added_date FROM offered_subjects WHERE id=?");
    $stmt->bind_param("i", $offered_id);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();

    if (!$subject) {
        $message = "Subject not found.";
    } else {
        $added_date = new DateTime($subject['added_date']);
        $now = new DateTime();
        $interval = $added_date->diff($now)->days;

        $days_open = 7; // Registration open for 7 days
        if ($interval >= $days_open) {
            $message = "Registration for this subject is closed. You can only register within {$days_open} days of offering.";
            $message_type = 'error';
        } else {
            // Check if already registered
            $stmt = $conn->prepare("SELECT * FROM registrations WHERE user_id=? AND subject_id=?");
            $stmt->bind_param("ii", $student_id, $offered_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $message = "You have already registered this subject.";
                $message_type = 'error';
            } else {
                // Check for time clash with other registered subjects
                $stmt = $conn->prepare("
                    SELECT r.subject_id 
                    FROM registrations r
                    JOIN offered_subjects os1 ON r.subject_id = os1.id
                    JOIN offered_subjects os2 ON os2.id = ?
                    WHERE r.user_id = ?
                    AND os1.day = os2.day
                    AND (os1.time_start < os2.time_end AND os1.time_end > os2.time_start)
                ");
                $stmt->bind_param("ii", $offered_id, $student_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $message = "Cannot add subject: Time clash detected. Please choose another subject.";
                    $message_type = 'error';
                } else {
                    $stmt = $conn->prepare("INSERT INTO registrations (user_id, subject_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $student_id, $offered_id);
                    $stmt->execute();
                    $message = "Subject added successfully!";
                    $message_type = 'success';

                }
            }
        }
    }
}

// --- Handle Drop (remove subject) ---
if (isset($_GET['drop'])) {
    $offered_id = intval($_GET['drop']);

    // Get the added_date of the subject
    $stmt = $conn->prepare("SELECT added_date FROM offered_subjects WHERE id=?");
    $stmt->bind_param("i", $offered_id);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();

    if (!$subject) {
        $message = "Subject not found.";
        $message_type = 'error';
    } else {
        $added_date = new DateTime($subject['added_date']);
        $now = new DateTime();
        $interval = $added_date->diff($now)->days;

        $days_open = 7; // Drop allowed within 7 days
        if ($interval >= $days_open) {
            $message = "You cannot drop this subject. Registration period has ended.";
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM registrations WHERE user_id=? AND subject_id=?");
            $stmt->bind_param("ii", $student_id, $offered_id);
            $stmt->execute();
            $message = "Subject dropped successfully!";
            $message_type = 'success';
        }
    }
}

// --- Fetch Offered Subjects (excluding registered) ---
$offered = $conn->prepare("
    SELECT os.id AS offered_id, sm.subject_name, sm.subject_code, sm.study_level, f.name AS faculty_name,
           os.day, os.time_start, os.time_end
    FROM offered_subjects os
    JOIN subjects_master sm ON os.subject_id = sm.id
    JOIN faculties f ON sm.faculty_id = f.id
    WHERE os.study_level=?
      AND os.id NOT IN (SELECT subject_id FROM registrations WHERE user_id=?)
    ORDER BY os.day, os.time_start
");
$offered->bind_param("si", $study_level, $student_id);
$offered->execute();
$offered_result = $offered->get_result();
$offered_subjects = [];
while ($row = $offered_result->fetch_assoc()) $offered_subjects[] = $row;

// Count for display
$offered_count = count($offered_subjects);

// --- Fetch Registered Subjects ---
$registered = $conn->prepare("
    SELECT r.subject_id AS offered_id, sm.subject_name, sm.subject_code, sm.study_level, f.name AS faculty_name,
           os.day, os.time_start, os.time_end
    FROM registrations r
    JOIN offered_subjects os ON r.subject_id = os.id
    JOIN subjects_master sm ON os.subject_id = sm.id
    JOIN faculties f ON sm.faculty_id = f.id
    WHERE r.user_id = ?
    ORDER BY os.day, os.time_start
");
$registered->bind_param("i", $student_id);
$registered->execute();
$registered_result = $registered->get_result();
$registered_subjects = [];
while ($row = $registered_result->fetch_assoc()) $registered_subjects[] = $row;
?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>My Timetable - University Portal</title>
<link rel="stylesheet" href="../assets/css/global_tables.css">
<style>
body { font-family: 'Roboto', sans-serif; background-color: #f4f6f9; margin: 0; }
.navbar { background-color: #004080; color: white; padding: 15px 30px; font-size: 18px; font-weight: 500; }
.container { width: 95%; max-width: 1200px; margin: 20px auto; }
.card { background-color: white; padding: 20px; margin-bottom: 20px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: 500; }
.btn-add { background-color: #28a745; color: white; }
.btn-drop { background-color: #dc3545; color: white; }
.btn-back {
    background-color: #ffc107; 
    color: white; 
    display: inline-block; 
    margin-bottom: 15px; 
}
.btn-back:hover {
    background-color: #004080; /* slightly darker yellow */
    color: white;
}
.success { color: green; font-weight: 500; margin-bottom: 10px; }
.error { color: red; font-weight: 500; margin-bottom: 10px; }
</style>
</head>
<body>

<div class="navbar">University Portal - My Timetable</div>
<div class="container">

<div style="margin-bottom: 20px;">
    <a href="dashboard.php" class="btn btn-back">← Back to Dashboard</a>
</div>
<div style="margin-bottom: 15px; padding: 10px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 4px;">
    <strong>Note:</strong> Registration and drop are only allowed within 7 days of the subject being offered. Please make sure there should be 3 subjects for short and 6 for long semester in your timetable.
</div>
<?php if($message): ?>
    <p class="<?= $message_type ?>"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>


<!-- Offered Subjects -->
<div class="card">
<h3>Offered Subjects</h3>
<table>
<tr>
    <th>Code</th>
    <th>Name</th>
    <th>Study Level</th>
    <th>Faculty</th>
    <th>Day</th>
    <th>Start</th>
    <th>End</th>
    <th>Action</th>
</tr>
<?php if($offered_subjects): ?>
    <?php foreach($offered_subjects as $s): ?>
    <tr>
        <td><?= htmlspecialchars($s['subject_code']) ?></td>
        <td><?= htmlspecialchars($s['subject_name']) ?></td>
        <td><?= htmlspecialchars($s['study_level']) ?></td>
        <td><?= htmlspecialchars($s['faculty_name']) ?></td>
        <td><?= htmlspecialchars($s['day']) ?></td>
        <td><?= date('h:i A', strtotime($s['time_start'])) ?></td>
        <td><?= date('h:i A', strtotime($s['time_end'])) ?></td>
        <td><a class="btn-add" href="?add=<?= $s['offered_id'] ?>">Add</a></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr><td colspan="8">No offered subjects available.</td></tr>
<?php endif; ?>
</table>
</div>

<!-- Registered Subjects -->
<div class="card">
<h3>Registered Subjects</h3>
<table>
<tr>
    <th>Code</th>
    <th>Name</th>
    <th>Study Level</th>
    <th>Faculty</th>
    <th>Day</th>
    <th>Start</th>
    <th>End</th>
    <th>Action</th>
</tr>
<?php if($registered_subjects): ?>
    <?php foreach($registered_subjects as $s): ?>
    <tr>
        <td><?= htmlspecialchars($s['subject_code']) ?></td>
        <td><?= htmlspecialchars($s['subject_name']) ?></td>
        <td><?= htmlspecialchars($s['study_level']) ?></td>
        <td><?= htmlspecialchars($s['faculty_name']) ?></td>
        <td><?= htmlspecialchars($s['day']) ?></td>
        <td><?= date('h:i A', strtotime($s['time_start'])) ?></td>
        <td><?= date('h:i A', strtotime($s['time_end'])) ?></td>
        <td><a class="btn-drop" href="?drop=<?= $s['offered_id'] ?>">Drop</a></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr><td colspan="8">You have not registered for any subjects yet.</td></tr>
<?php endif; ?>
</table>
</div>

</div>
</body>
</html>
