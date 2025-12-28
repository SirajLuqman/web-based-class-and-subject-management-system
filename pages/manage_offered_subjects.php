<?php
session_start();
require '../includes/db.php';

// --- AJAX HANDLER (Run this part only if requested via JavaScript) ---
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_subjects') {
    $fac_id = $_GET['faculty_id'] ?? '';
    $lvl    = $_GET['study_level'] ?? '';
    
    $subjects_list = [];
    if ($fac_id && $lvl) {
        $stmt = $conn->prepare("SELECT id, subject_name, subject_code FROM subjects_master WHERE faculty_id = ? AND study_level = ? ORDER BY subject_name");
        $stmt->bind_param("is", $fac_id, $lvl);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $subjects_list[] = $row;
        }
    }
    // Return JSON and stop execution
    header('Content-Type: application/json');
    echo json_encode($subjects_list);
    exit;
}
// ---------------------------------------------------------------------

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];

// Initialize edit variables
$edit_mode = false;
$edit_subject = null;

// Determine selected faculty and study level (For initial page load)
$selected_faculty = '';
$selected_level   = '';

// Check if edit mode
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("
        SELECT os.id, os.day, os.time_start, os.time_end, os.capacity,
               sm.id AS subject_id, sm.subject_name, sm.subject_code, sm.faculty_id, sm.study_level
        FROM offered_subjects os
        JOIN subjects_master sm ON os.subject_id = sm.id
        WHERE os.id=?
    ");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    if ($edit_result->num_rows == 1) {
        $edit_mode = true;
        $edit_subject = $edit_result->fetch_assoc();
        
        // Pre-fill for Edit Mode
        $selected_faculty = $edit_subject['faculty_id'];
        $selected_level   = $edit_subject['study_level'];
    }
}

// If form was posted (error case), preserve selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_faculty = $_POST['faculty'] ?? '';
    $selected_level   = $_POST['study_level'] ?? '';
}

// Fetch subjects for selected faculty (For initial load only - PHP Fallback)
$subjects = [];
if (!empty($selected_faculty) && !empty($selected_level)) {
    $stmt = $conn->prepare("
        SELECT id, subject_name, subject_code
        FROM subjects_master
        WHERE faculty_id = ? AND study_level = ?
        ORDER BY subject_name
    ");
    $stmt->bind_param("is", $selected_faculty, $selected_level);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// CORRECTED hasTimeConflict() function
function hasTimeConflict($conn, $day, $start_time, $end_time, $faculty_id, $study_level, $exclude_id = null) {
    if ($exclude_id) {
        $sql = "SELECT os.id, sm.subject_name, os.time_start, os.time_end
                FROM offered_subjects os
                JOIN subjects_master sm ON os.subject_id = sm.id
                WHERE sm.faculty_id = ? 
                  AND sm.study_level = ? 
                  AND os.day = ? 
                  AND os.id != ? 
                  AND os.time_start < ? 
                  AND os.time_end > ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $faculty_id, $study_level, $day, $exclude_id, $end_time, $start_time);
    } else {
        $sql = "SELECT os.id, sm.subject_name, os.time_start, os.time_end
                FROM offered_subjects os
                JOIN subjects_master sm ON os.subject_id = sm.id
                WHERE sm.faculty_id = ? 
                  AND sm.study_level = ? 
                  AND os.day = ? 
                  AND os.time_start < ? 
                  AND os.time_end > ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $faculty_id, $study_level, $day, $end_time, $start_time);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


// Fetch all faculties
$faculties = [];
$res = $conn->query("SELECT * FROM faculties ORDER BY name");
while ($f = $res->fetch_assoc()) {
    $faculties[] = $f;
}

// Handle Add/Edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add']) || isset($_POST['edit']))) {
    $id = $_POST['id'] ?? null;
    $faculty_id = $_POST['faculty'] ?? '';
    $study_level  = $_POST['study_level'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $day = $_POST['day'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $capacity = (int)($_POST['capacity'] ?? 0);

    $errors = [];

    if (!$faculty_id) $errors[] = "Faculty is required.";
    if (!$subject_id) $errors[] = "Subject is required.";
    if (!in_array($day, $days)) $errors[] = "Invalid day.";
    if (!$start_time || !$end_time) $errors[] = "Start and end times are required.";
    if ($start_time >= $end_time) $errors[] = "Start time must be before end time.";
    if ($capacity < 0 || $capacity > 99) $errors[] = "Capacity must be 0-99.";

    // Time conflict check
    $conflicts = [];
    $show_conflict_modal = false;

    if (empty($errors)) {
        $conflicts = hasTimeConflict($conn, $day, $start_time, $end_time, $faculty_id, $study_level, $id ?? null);

        // --- NEW: Show conflict modal if conflicts found and not force_add ---
        if (!empty($conflicts) && !isset($_POST['force_add'])) {
            $show_conflict_modal = true;
            // Stop here so form is rendered again with modal
        }
    }

    // --- NEW: If no errors and no conflict modal OR force_add pressed, insert record ---
    if (empty($errors) && (!$show_conflict_modal || isset($_POST['force_add']))) {
        if (isset($_POST['add'])) {
            $stmt = $conn->prepare("
                INSERT INTO offered_subjects 
                (subject_id, day, time_start, time_end, capacity, study_level)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssis",
                $subject_id,
                $day,
                $start_time,
                $end_time,
                $capacity,
                $study_level
);
            $stmt->execute();
            $message = "Subject added successfully!";
        } elseif (isset($_POST['edit'])) {
            $stmt = $conn->prepare("UPDATE offered_subjects SET subject_id=?, day=?, time_start=?, time_end=?, capacity=? WHERE id=?");
            $stmt->bind_param("isssii", $subject_id, $day, $start_time, $end_time, $capacity, $id);
            $stmt->execute();
            $message = "Subject updated successfully!";
        }
        $_SESSION['message'] = $message;
        header("Location: manage_offered_subjects.php");
        exit;
    } elseif (!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM offered_subjects WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['message'] = "Subject deleted successfully!";
    header("Location: manage_offered_subjects.php");
    exit;
}

// Fetch offered subjects list
$filter_day = $_GET['filter_day'] ?? '';
$search_query = $_GET['search'] ?? '';
$sql = "
    SELECT os.id, os.day, os.time_start, os.time_end, os.capacity,
           sm.subject_name, sm.subject_code, sm.faculty_id, sm.study_level, f.name AS faculty_name
    FROM offered_subjects os
    JOIN subjects_master sm ON os.subject_id = sm.id
    JOIN faculties f ON sm.faculty_id = f.id
    WHERE 1
";
$params = [];
$types = '';

if ($filter_day && in_array($filter_day, $days)) {
    $sql .= " AND os.day=?";
    $types .= 's';
    $params[] = $filter_day;
}
if ($search_query) {
    $sql .= " AND (sm.subject_name LIKE ? OR sm.subject_code LIKE ?)";
    $types .= 'ss';
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}
$sql .= " ORDER BY f.name, os.day, os.time_start";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Manage Offered Subjects - University Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/global_tables.css">

<style>
    body { font-family: 'Roboto', sans-serif; background-color: #f4f6f9; margin:0; }
    .navbar { background-color: #004080; color: white; padding: 15px 30px; font-size: 18px; font-weight: 500; }
    .container { width: 95%; max-width: 1200px; margin: 20px auto; }
    .card { background-color: white; padding: 20px; margin-bottom: 20px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    input, select { width: 100%; padding: 8px; margin-top: 4px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc; }
    button, .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block; }
    .btn-add { background-color: #28a745; color: white; }
    .btn-edit { background-color: #ffc107; color: white; }
    .btn-delete { background-color: #dc3545; color: white; }
    .success { color: green; font-weight: 500; margin-bottom:10px; }
    .error { color: red; font-weight: 500; margin-bottom:10px; }
    tr:hover { background-color: #f1f1f1; }
    tr.conflict { background-color: #f8d7da !important; }
    .search-bar-container { display:flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .search-bar { display: flex; gap: 10px; align-items: center; flex: -10; }
    .search-bar input[type="text"] { flex: 1; height: 32px; padding: 6px 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; box-sizing: border-box; }
    .search-bar button { height: 32px; padding: 6px 12px; background: #004080; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px; transition: 0.3s; }
    .search-bar button:hover { background: #0066cc; }
    .filter-form select { width: 100px; padding: 6px 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; }

    /* Modal Styles */
    .modal {
        display: block; /* Show by default if PHP sets it */
        position: fixed;
        z-index: 1000;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .modal-content h3 { color: #dc3545; }
    .modal-content ul { margin: 10px 0; padding-left: 20px; }
    .modal-content button { margin-right: 10px; }


</style>
<script>
// --- NEW: AJAX Function to fetch subjects smoothly ---
async function updateSubjects() {
    const facultyId = document.getElementById('facultySelect').value;
    const studyLevel = document.getElementById('levelSelect').value;
    const subjectSelect = document.getElementById('subjectSelect');

    // If either i s missing, reset and stop
    if (!facultyId || !studyLevel) {
        subjectSelect.innerHTML = '<option value="">Select Faculty & Level first</option>';
        return;
    }

    // Show loading text
    subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';

    try {
        // Fetch data from THIS SAME FILE using the ajax_action param
        const response = await fetch(`manage_offered_subjects.php?ajax_action=get_subjects&faculty_id=${facultyId}&study_level=${studyLevel}`);
        const subjects = await response.json();

        // Clear and populate
        subjectSelect.innerHTML = '<option value="">Select Subject</option>';
        if (subjects.length > 0) {
            subjects.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.id;
                option.textContent = `${sub.subject_name} (${sub.subject_code})`;
                subjectSelect.appendChild(option);
            });
        } else {
            subjectSelect.innerHTML = '<option value="">No subjects found</option>';
        }
    } catch (error) {
        console.error('Error fetching subjects:', error);
        subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
    }
}

function validateForm(form) {
    if (!form.faculty.value) { alert('Select faculty'); return false; }
    if (!form.subject_id.value) { alert('Select subject'); return false; }
    if (!form.start_time.value || !form.end_time.value || form.start_time.value >= form.end_time.value) {
        alert('Start time must be before end time'); return false;
    }
    const cap = parseInt(form.capacity.value);
    if (cap < 0 || cap > 99) { alert('Capacity must be 0-99'); return false; }
    return true;
}
</script>
</head>
<body>
<div class="navbar">University Portal - Manage Offered Subjects</div>
<div class="container">

<div style="margin-bottom: 20px;">
    <a href="dashboard.php" class="btn btn-edit">← Back to Dashboard</a>
</div>

<?php if (!empty($_SESSION['message'])): ?>
    <p class="success"><?= htmlspecialchars($_SESSION['message']) ?></p>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<div class="card">
<?php if ($edit_mode && $edit_subject): ?>
<h3>Edit Offered Subject</h3>
<form method="post" onsubmit="return validateForm(this)">
    <input type="hidden" name="id" value="<?= $edit_subject['id'] ?>">
    <label>Faculty:
        <select name="faculty" id="facultySelect" required onchange="updateSubjects()">
            <option value="">Select Faculty</option>
            <?php foreach($faculties as $f): ?>
                <option value="<?= $f['id'] ?>" <?= ($selected_faculty == $f['id']) ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Study Level:
    <select name="study_level" id="levelSelect" required onchange="updateSubjects()">
        <option value="">Select Level</option>
        <option value="Diploma" <?= ($selected_level=='Diploma')?'selected':'' ?>>Diploma</option>
        <option value="Bachelor" <?= ($selected_level=='Bachelor')?'selected':'' ?>>Bachelor</option>
        <option value="Master" <?= ($selected_level=='Master')?'selected':'' ?>>Master</option>
    </select>
    </label>
    <label>Subject:
        <select name="subject_id" id="subjectSelect" required>
            <option value=""><?= ($selected_faculty && $selected_level) ? 'Select Subject' : 'Select Faculty & Level first' ?></option>
            <?php foreach($subjects as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (($edit_subject['subject_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['subject_name']) ?> (<?= htmlspecialchars($s['subject_code']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Day:
        <select name="day" required>
            <?php foreach($days as $d): ?>
                <option value="<?= $d ?>" <?= ($edit_subject['day'] == $d) ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Start Time: <input type="time" name="start_time" value="<?= htmlspecialchars($edit_subject['time_start']) ?>" required></label>
    <label>End Time: <input type="time" name="end_time" value="<?= htmlspecialchars($edit_subject['time_end']) ?>" required></label>
    <label>Capacity: <input type="number" name="capacity" value="<?= htmlspecialchars($edit_subject['capacity']) ?>" max="99" required></label>
    <button type="submit" name="edit" class="btn btn-add">Update</button>
    <a href="manage_offered_subjects.php" class="btn btn-edit">Cancel</a>
</form>

<?php else: ?>
<h3>Add Offered Subject</h3>
<form method="post" onsubmit="return validateForm(this)">
    <label>Faculty:
        <select name="faculty" id="facultySelect" required onchange="updateSubjects()">
            <option value="">Select Faculty</option>
            <?php foreach($faculties as $f): ?>
                <option value="<?= $f['id'] ?>" <?= ($selected_faculty == $f['id']) ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Study Level:
    <select name="study_level" id="levelSelect" required onchange="updateSubjects()">
        <option value="">Select Level</option>
        <option value="Diploma" <?= ($selected_level=='Diploma')?'selected':'' ?>>Diploma</option>
        <option value="Bachelor" <?= ($selected_level=='Bachelor')?'selected':'' ?>>Bachelor</option>
        <option value="Master" <?= ($selected_level=='Master')?'selected':'' ?>>Master</option>
    </select>
    </label>
    <label>Subject:
        <select name="subject_id" id="subjectSelect" required>
            <option value=""><?= ($selected_faculty && $selected_level) ? 'Select Subject' : 'Select Faculty & Level first' ?></option>
            <?php foreach($subjects as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (($_POST['subject_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['subject_name']) ?> (<?= htmlspecialchars($s['subject_code']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Day:
        <select name="day" required>
            <?php foreach($days as $d): ?>
                <option value="<?= $d ?>" <?= (($_POST['day'] ?? '') == $d) ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Start Time: <input type="time" name="start_time" value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>" required></label>
    <label>End Time: <input type="time" name="end_time" value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>" required></label>
    <label>Capacity: <input type="number" name="capacity" value="<?= htmlspecialchars($_POST['capacity'] ?? 0) ?>" max="99" required></label>
    <button type="submit" name="add" class="btn btn-add">Add Subject</button>
</form>
<?php endif; ?>
</div>

<?php if (!empty($conflicts) && $show_conflict_modal): ?>
    <div id="conflictModal" class="modal">
        <div class="modal-content">
            <h3>Time Conflict Detected!</h3>
            <p>The following subjects may clash with your selected time:</p>
            <ul>
                <?php foreach($conflicts as $c): ?>
                    <li><?= htmlspecialchars($c['subject_name']) ?> (<?= date('h:i A', strtotime($c['time_start'])) ?> - <?= date('h:i A', strtotime($c['time_end'])) ?>)</li>
                <?php endforeach; ?>
            </ul>
            <form method="post">
                <?php
                // Re-add all form fields as hidden so data is preserved
                foreach($_POST as $key => $value){
                    echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'">';
                }
                ?>
                <button type="submit" name="force_add" class="btn btn-add">Proceed Anyway</button>
                <button type="button" onclick="document.getElementById('conflictModal').style.display='none'" class="btn btn-edit">Edit Details</button>
            </form>
        </div>
    </div>
<?php endif; ?>


<div class="card">
<div class="search-bar-container">
    <h3>Existing Offered Subjects</h3>
    <form method="get" class="search-bar">
        <input type="text" name="search" placeholder="Enter Subject Code" value="<?= htmlspecialchars($search_query) ?>">
        <button type="button" onclick="window.location.href='manage_offered_subjects.php'">⟳</button>
        <button type="submit">Search</button>
    </form>
</div>

<form class="filter-form" method="get" action="">
    <label>Filter by Day:
        <select name="filter_day" onchange="this.form.submit()">
            <option value="">All Days</option>
            <?php foreach($days as $d): ?>
                <option value="<?= $d ?>" <?= $filter_day==$d?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Faculty</th>
                <th>Study Level</th>
                <th>Subject</th>
                <th>Code</th>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
                <th>Capacity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): 
            $conflict = hasTimeConflict($conn, $row['day'], $row['time_start'], $row['time_end'], $row['faculty_id'], $row['id']);
        ?>
            <tr class="<?= strtolower($row['day']) ?> <?= $conflict ? 'conflict' : '' ?>">
                <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                <td><?= htmlspecialchars($row['study_level']) ?></td>
                <td><?= htmlspecialchars($row['subject_name']) ?></td>
                <td><?= htmlspecialchars($row['subject_code']) ?></td>
                <td><?= htmlspecialchars($row['day']) ?></td>
                <td><?= date('h:i A', strtotime($row['time_start'])) ?></td>
                <td><?= date('h:i A', strtotime($row['time_end'])) ?></td>
                <td><?= htmlspecialchars($row['capacity']) ?></td>
                <td>
                    <a class="btn btn-edit" href="?edit=<?= $row['id'] ?>">Edit</a>
                    <button class="btn btn-delete" data-id="<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const id = this.getAttribute('data-id');
        if (confirm('Are you sure you want to delete this subject?')) {
            window.location.href = '?delete=' + id;
        }
        // else do nothing if Cancel clicked
    });
});
</script>


</div>
</body>
</html>