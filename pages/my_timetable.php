<?php
session_start();
require '../includes/db.php';

// Only students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Fetch faculty & study level
$stmt = $conn->prepare("SELECT faculty_id, study_level_id FROM users WHERE id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$sr = $stmt->get_result()->fetch_assoc();
$faculty_id = $sr['faculty_id'];
$study_level_id = $sr['study_level_id'];

// Count registered subjects
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE user_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$registered_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Count offered subjects
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM offered_subjects");
$stmt->execute();
$offered_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Fetch registered subjects
$stmt = $conn->prepare("
    SELECT sm.subject_code, sm.subject_name, os.day, os.time_start, os.time_end
    FROM registrations r
    JOIN offered_subjects os ON r.subject_id = os.id
    JOIN subjects_master sm ON os.subject_id = sm.id
    WHERE r.user_id=? AND r.status='registered'
    ORDER BY FIELD(os.day, 'Monday','Tuesday','Wednesday','Thursday','Friday'), os.time_start
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Build timetable array
$timetable = [];
while ($row = $result->fetch_assoc()) {
    $timetable[$row['day']][] = $row;
}
?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>My Timetable</title>
<link rel="stylesheet" href="../assets/css/global_tables.css">

<style>
body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; }
.navbar { background-color: #004080; color: white; padding: 15px; font-size: 20px; }
.container { width: 90%; margin: auto; margin-top: 20px; }

/* Buttons */
.btn { padding: 8px 14px; text-decoration: none; border-radius: 4px; }
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

/* Cards */
.card {
    background: white;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 25px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Tables */
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
th { background-color: #f8f9fa; font-weight: bold; }

/* Note box */
.note-box {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
}
</style>
</head>

<body>

<div class="navbar">University Portal - My Timetable</div>
<div class="container">

<div style="margin-bottom: 20px;">
    <a href="dashboard.php" class="btn btn-back">← Back to Dashboard</a>
</div>

<div class="note-box">
    <strong>Note:</strong> Your timetable displays all subjects you have registered. 
    Any Add/Drop done from Manage Timetable reflects here immediately.
</div>

<!-- Summary Cards -->
<div class="card">
<h3>Your Summary</h3>
<table>
<tr>
    <th>Total Registered</th>
    <th>Offered Subjects (For You)</th>
</tr>
<tr>
    <td><?= $registered_count ?></td>
    <td><?= $offered_count ?></td>
</tr>
</table>
</div>

<!-- Timetable -->
<div class="card">
<h3>Your Weekly Timetable</h3>
<table>
<tr>
    <th>Day</th>
    <th>Subject Code</th>
    <th>Subject Name</th>
    <th>Start Time</th>
    <th>End Time</th>
</tr>

<?php
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];

foreach ($days as $day):
    if (!empty($timetable[$day])):
        foreach ($timetable[$day] as $s):
?>
<tr>
    <td><?= $day ?></td>
    <td><?= htmlspecialchars($s['subject_code']) ?></td>
    <td><?= htmlspecialchars($s['subject_name']) ?></td>
    <td><?= date('h:i A', strtotime($s['time_start'])) ?></td>
    <td><?= date('h:i A', strtotime($s['time_end'])) ?></td>
</tr>
<?php endforeach; else: ?>
<tr>
    <td><?= $day ?></td>
    <td colspan="4">No subjects</td>
</tr>
<?php endif; endforeach; ?>

</table>
</div>

</div>
</body>
</html>
