<?php
session_start();
require '../includes/db.php';

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle Add/Edit Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subject'])) {
    $id = $_POST['subject_id'] ?? null;
    $name = trim($_POST['subject_name'] ?? '');
    $code = trim($_POST['subject_code'] ?? '');
    $faculty_id = $_POST['faculty_id'] ?? '';
    $study_level = $_POST['study_level'] ?? '';

    if (!$name || !$code || !$faculty_id) {
        $error = "All fields are required.";
    } else {

        // ---------------------------------------------------
        // ✅ DUPLICATE CHECK ONLY WHEN ADDING A NEW SUBJECT
        // ---------------------------------------------------
        if (!$id) {  // Only check duplicates on "Add", not on "Edit"
            $check = $conn->prepare("
                SELECT id FROM subjects_master
                WHERE subject_name = ? 
                  AND subject_code = ? 
                  AND faculty_id = ? 
                  AND study_level = ?
                LIMIT 1
            ");
            $check->bind_param("ssis", $name, $code, $faculty_id, $study_level);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $_SESSION['message'] = "A subject with the same name and code already exists for this faculty and level.";
                $_SESSION['message_type'] = "danger";
                header("Location: manage_subjects.php");
                exit;
            }
        }

        // ---------------------------------------------------
        // ✅ UPDATE EXISTING SUBJECT
        // ---------------------------------------------------
        if ($id) {
            $stmt = $conn->prepare("
                UPDATE subjects_master 
                SET subject_name=?, subject_code=?, faculty_id=?, study_level=?
                WHERE id=?
            ");
            $stmt->bind_param("ssisi", $name, $code, $faculty_id, $study_level, $id);
            $stmt->execute();
            $message = "Subject updated successfully!";
        }

        // ---------------------------------------------------
        // ✅ INSERT NEW SUBJECT
        // ---------------------------------------------------
        else {
            $stmt = $conn->prepare("
                INSERT INTO subjects_master (subject_name, subject_code, faculty_id, study_level)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssis", $name, $code, $faculty_id, $study_level);
            $stmt->execute();
            $message = "New subject added successfully!";
        }

        $_SESSION['message'] = $message;
        header("Location: manage_subjects.php");
        exit;
    }
}


// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM subjects_master WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['message'] = "Subject deleted successfully!";
    header("Location: manage_subjects.php");
    exit;
}

// Prefill edit form
$edit_mode = false;
$edit_subject = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM subjects_master WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $edit_mode = true;
        $edit_subject = $res->fetch_assoc();
    }
}

// Fetch faculties
$faculties = [];
$res = $conn->query("SELECT * FROM faculties ORDER BY name");
while ($f = $res->fetch_assoc()) $faculties[] = $f;

// Handle search by subject code
$search_code = $_GET['search_code'] ?? '';

// Fetch subjects with optional search
$subjects = [];
if ($search_code) {
    $stmt = $conn->prepare("
        SELECT s.*, f.name AS faculty_name
        FROM subjects_master s
        LEFT JOIN faculties f ON s.faculty_id=f.id
        WHERE s.subject_code LIKE ?
        ORDER BY s.subject_name
    ");
    $like_code = "%$search_code%";
    $stmt->bind_param("s", $like_code);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("
        SELECT s.*, f.name AS faculty_name
        FROM subjects_master s
        LEFT JOIN faculties f ON s.faculty_id=f.id
        ORDER BY f.name, s.subject_name
    ");
}
while ($row = $res->fetch_assoc()) $subjects[] = $row;
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
body { 
    font-family: 'Roboto', sans-serif; 
    background-color: #f4f6f9; 
    margin: 0; 
}

.navbar { 
    background-color: #004080; 
    color: white; 
    padding: 15px 30px; 
    font-size: 18px; 
    font-weight: 500; 
}

.container { 
    width: 95%; 
    max-width: 1200px; 
    margin: 20px auto; 
}

.card { 
    background-color: white; 
    padding: 20px; 
    margin-bottom: 20px; 
    border-radius: 6px; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
}

input, select { 
    width: 100%; 
    padding: 8px; 
    margin-top: 4px; 
    margin-bottom: 12px; 
    border-radius: 4px; 
    border: 1px solid #ccc; 
    box-sizing: border-box;
}

button, .btn { 
    padding: 8px 15px; 
    border: none; 
    border-radius: 4px; 
    cursor: pointer; 
    font-weight: 500; 
    text-decoration: none; 
    display: inline-block; 
}

.btn-add { background-color: #28a745; color: white; }
.btn-edit { background-color: #ffc107; color: white; }
.btn-delete { background-color: #dc3545; color: white; }

.success { color: green; font-weight: 500; margin-bottom: 10px; }
.error { color: red; font-weight: 500; margin-bottom: 10px; }

/* Search Bar */
.search-bar-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.search-bar {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-bar input[type="text"] {
    flex: 1;
    height: 32px;
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    box-sizing: border-box;
}

.search-bar button {
    height: 32px;
    padding: 6px 12px;
    background: #004080;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
    transition: 0.3s;
}

.search-bar button:hover {
    background: #0066cc;
}
</style>

</head>
<body>

<div class="navbar">University Portal - Manage Subjects</div>
<div class="container">

<div style="margin-bottom: 20px;">
    <a href="dashboard.php" class="btn btn-edit">← Back to Dashboard</a>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <?php 
        $type = $_SESSION['message_type'] ?? 'success'; 
        $class = ($type === 'danger') ? 'error' : 'success'; 
    ?>
    <p class="<?= $class ?>"><?= htmlspecialchars($_SESSION['message']) ?></p>
    <?php 
        unset($_SESSION['message']); 
        unset($_SESSION['message_type']); 
    ?>
<?php endif; ?>


<?php if (!empty($error)): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<div class="card">
<h3><?= $edit_mode ? "Edit Subject" : "Add New Subject" ?></h3>
<form method="post">
    <input type="hidden" name="subject_id" value="<?= $edit_subject['id'] ?? '' ?>">
    <label>Subject Name:</label>
    <input type="text" name="subject_name" value="<?= htmlspecialchars($edit_subject['subject_name'] ?? '') ?>" required>
    <label>Subject Code:</label>
    <input type="text" name="subject_code" value="<?= htmlspecialchars($edit_subject['subject_code'] ?? '') ?>" required>
    <label>Faculty:</label>
    <select name="faculty_id" required>
        <option value="">-- Select Faculty --</option>
        <?php foreach($faculties as $f): ?>
            <option value="<?= $f['id'] ?>" <?= (isset($edit_subject['faculty_id']) && $edit_subject['faculty_id']==$f['id'])?'selected':'' ?>>
                <?= htmlspecialchars($f['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label>Study Level:</label>
    <select name="study_level" required>
        <option value="">-- Select Study Level --</option>
        <option value="Diploma" <?= (isset($edit_subject['study_level']) && $edit_subject['study_level']=='Diploma')?'selected':'' ?>>Diploma</option>
        <option value="Bachelor" <?= (isset($edit_subject['study_level']) && $edit_subject['study_level']=='Bachelor')?'selected':'' ?>>Bachelor</option>
        <option value="Master" <?= (isset($edit_subject['study_level']) && $edit_subject['study_level']=='Master')?'selected':'' ?>>Master</option>
    </select>

    <button type="submit" name="save_subject" class="btn btn-add"><?= $edit_mode ? "Update Subject" : "Add Subject" ?></button>
    <?php if($edit_mode): ?>
        <a href="manage_subjects.php" class="btn btn-edit">Cancel</a>
    <?php endif; ?>
</form>
</div>

<div class="card">
<div class="search-bar-container">
    <h3>All Subjects</h3>
    <form method="GET" class="search-bar">
        <input type="text" name="search_code" placeholder="Enter Subject Code" value="<?= htmlspecialchars($search_code ?? '') ?>">
        <button type="button" onclick="window.location.href='manage_subjects.php'">⟳</button>
        <button type="submit">Search</button>
    </form>
</div>

<div class="table-container">
<table>
<tr>
    <th>ID</th>
    <th>Subject Name</th>
    <th>Subject Code</th>
    <th>Study Level</th>
    <th>Faculty</th>
    <th>Actions</th>
</tr>
<?php foreach($subjects as $s): ?>
<tr>
    <td><?= $s['id'] ?></td>
    <td><?= htmlspecialchars($s['subject_name']) ?></td>
    <td><?= htmlspecialchars($s['subject_code']) ?></td>
    <td><?= htmlspecialchars($s['study_level']) ?></td>
    <td><?= htmlspecialchars($s['faculty_name']) ?></td>
    <td class="actions">
        <a class="btn btn-edit" href="?edit=<?= $s['id'] ?>">Edit</a>
        <button type="button" class="btn btn-delete" data-id="<?= $s['id'] ?>">Delete</button>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>
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
