<?php
session_start();
require '../includes/db.php';

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Display messages
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// Generate random password
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$!';
    return substr(str_shuffle($chars), 0, $length);
}

// Handle Add/Edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $faculty_id = $_POST['faculty_id'];
    $study_level_id = $_POST['study_level_id'];
    $student_id = $_POST['student_id'] ?? null;

    // Check duplicate username
    $dup_query = "SELECT id FROM users WHERE username=? AND role='student'";
    if ($student_id) $dup_query .= " AND id != ?";
    $stmt = $conn->prepare($dup_query);
    if ($student_id) {
        $stmt->bind_param("si", $username, $student_id);
    } else {
        $stmt->bind_param("s", $username);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $_SESSION['error'] = "Username already exists. Please choose another.";
        header("Location: manage_students.php" . ($student_id ? "?edit=$student_id" : ""));
        exit;
    }

    // Handle password (ensure $hashedPassword is always set)
    if (!empty($_POST['password'])) {
        $plainPassword = $_POST['password'];
    } elseif (!$student_id) {
        $plainPassword = generatePassword(8);
    }
    if (isset($plainPassword)) {
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    if ($student_id) {
        // Edit existing student
        if (!empty($_POST['password'])) {
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, faculty_id=?, study_level_id=?, password=? WHERE id=? AND role='student'");
            $stmt->bind_param("sssissi", $username, $full_name, $email, $faculty_id, $study_level_id, $hashedPassword, $student_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, faculty_id=?, study_level_id=? WHERE id=? AND role='student'");
            $stmt->bind_param("sssiii", $username, $full_name, $email, $faculty_id, $study_level_id, $student_id);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = "Student updated successfully!";
            header("Location: manage_students.php");
            exit;
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
            header("Location: manage_students.php" . ($student_id ? "?edit=$student_id" : ""));
            exit;
        }
    } else {
        // Insert new student
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email, faculty_id, study_level_id) VALUES (?, ?, 'student', ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $username, $hashedPassword, $full_name, $email, $faculty_id, $study_level_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Student created successfully!<br>Username: <b>$username</b><br>Temporary Password: <b>$plainPassword</b>";
            header("Location: manage_students.php");
            exit;
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
            header("Location: manage_students.php");
            exit;
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='student'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Student deleted successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
    header("Location: manage_students.php");
    exit;
}

// Prefill edit form
$edit_student = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='student'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 1) $edit_student = $res->fetch_assoc();
}

// Fetch faculties
$faculties = [];
$res = $conn->query("SELECT * FROM faculties ORDER BY name");
while ($f = $res->fetch_assoc()) $faculties[] = $f;

// Fetch study levels
$levels = [];
$res = $conn->query("SELECT * FROM study_levels ORDER BY id");
while ($l = $res->fetch_assoc()) $levels[] = $l;

// Handle search
$search_id = $_GET['search_id'] ?? '';

// Fetch all students with optional search
$students = [];
$sql = "SELECT u.*, f.name AS faculty_name, s.level_name
        FROM users u
        LEFT JOIN faculties f ON u.faculty_id=f.id
        LEFT JOIN study_levels s ON u.study_level_id=s.id
        WHERE u.role='student'";
if ($search_id) {
    $sql .= " AND u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $search_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $sql .= " ORDER BY u.id DESC";
    $res = $conn->query($sql);
}
while ($row = $res->fetch_assoc()) $students[] = $row;
?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Manage Students - University Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/global_tables.css">


<style>
body { font-family: 'Roboto', sans-serif; background-color: #f4f6f9; margin:0; }
.navbar { background-color: #004080; color: white; padding: 15px 30px; font-size: 18px; font-weight: 500; }
.container { width: 95%; max-width: 1000px; margin: 20px auto; }
.card { background-color: white; padding: 20px; margin-bottom: 20px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
input, select { width: 100%; padding: 8px; margin-top: 4px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc; }
button, .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block; }
.btn-add { background-color: #28a745; color: white; }
.btn-edit { background-color: #ffc107; color: white; }
.btn-delete { background-color: #dc3545; color: white; }
.success { color: green; font-weight: 500; margin-bottom:10px; }
.error { color: red; font-weight: 500; margin-bottom:10px; }

/* Search Bar */
.search-bar-container {
    display:flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.search-bar {
    display: flex;
    gap: 10px;
    align-items: center;
}
.search-bar input[type="number"] {
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
<div class="navbar">University Portal - Manage Students</div>
<div class="container">

<div style="margin-bottom: 20px;">
    <a href="dashboard.php" class="btn btn-edit">← Back to Dashboard</a>
</div>

<?php if (!empty($message)): ?><p class="success"><?= $message ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="error"><?= $error ?></p><?php endif; ?>

<div class="card">
<h3><?= $edit_student ? 'Edit Student' : 'Add New Student' ?></h3>
<form method="POST">
    <?php if ($edit_student): ?>
        <input type="hidden" name="student_id" value="<?= $edit_student['id'] ?>">
    <?php endif; ?>
    <label>Full Name:</label>
    <input type="text" name="full_name" value="<?= htmlspecialchars($edit_student['full_name'] ?? ($_POST['full_name'] ?? '')) ?>" required>
    <label>Username (unique):</label>
    <input type="text" name="username" value="<?= htmlspecialchars($edit_student['username'] ?? ($_POST['username'] ?? '')) ?>" required>
    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($edit_student['email'] ?? ($_POST['email'] ?? '')) ?>" required>
    <label>Faculty:</label>
    <select name="faculty_id" required>
        <option value="">Select Faculty</option>
        <?php foreach($faculties as $f): ?>
            <option value="<?= $f['id'] ?>" <?= (($edit_student['faculty_id'] ?? $_POST['faculty_id'] ?? '') == $f['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($f['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label>Study Level:</label>
    <select name="study_level_id" required>
        <option value="">Select Level</option>
        <?php foreach($levels as $l): ?>
            <option value="<?= $l['id'] ?>" <?= (($edit_student['study_level_id'] ?? $_POST['study_level_id'] ?? '') == $l['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['level_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label>Password (optional, leave blank to auto-generate or keep existing):</label>
    <input type="text" name="password" value="">
    <button type="submit" class="btn btn-add"><?= $edit_student ? 'Update Student' : 'Create Student' ?></button>
    <?php if ($edit_student): ?>
        <a href="manage_students.php" class="btn btn-edit">Cancel</a>
    <?php endif; ?>
</form>
</div>

<div class="card">
<div class="search-bar-container">
    <h3>All Students</h3>
    <form method="GET" class="search-bar">
        <input type="number" name="search_id" placeholder="Enter Student ID" value="<?= htmlspecialchars($search_id) ?>">
        <button type="button" onclick="window.location.href='manage_students.php'" 
            style="height:32px; border:none; cursor:pointer; font-size:16px; border-radius:4px;">⟳</button>

        <button type="submit">Search</button>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Faculty</th>
                <th>Study Level</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($students as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['username']) ?></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= htmlspecialchars($s['faculty_name']) ?></td>
                <td><?= htmlspecialchars($s['level_name']) ?></td>
                <td style="min-width:120px;">
                    <a class="btn btn-edit" href="?edit=<?= $s['id'] ?>">Edit</a>
                    <button type="button" class="btn btn-delete" data-id="<?= $s['id'] ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
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
