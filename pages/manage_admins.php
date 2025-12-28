<?php
session_start();
require '../includes/db.php';

// Only allow super-admin or admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Display messages
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// Generate random password
function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$!';
    return substr(str_shuffle($chars), 0, $length);
}

// Handle Add/Edit Admin
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $nic_number = trim($_POST['nic_number'] ?? '');
    $admin_id = $_POST['admin_id'] ?? null;

    // Check duplicate username
    $dup_query = "SELECT id FROM users WHERE username=? AND role='admin'";
    if ($admin_id) $dup_query .= " AND id != ?";
    $stmt = $conn->prepare($dup_query);
    if ($admin_id) {
        $stmt->bind_param("si", $username, $admin_id);
    } else {
        $stmt->bind_param("s", $username);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $_SESSION['error'] = "Username already exists. Please choose another.";
        header("Location: manage_admin.php" . ($admin_id ? "?edit=$admin_id" : ""));
        exit;
    }

    // Handle password
    if (!empty($_POST['password'])) {
        $plainPassword = $_POST['password'];
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    } elseif (!$admin_id) {
        $plainPassword = generatePassword(10);
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    if ($admin_id) {
        // Update existing admin
        if (!empty($_POST['password'])) {
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, password=?, nic_number=? WHERE id=? AND role='admin'");
            $stmt->bind_param("sssssi", $username, $full_name, $email, $hashedPassword, $nic_number, $admin_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, nic_number=? WHERE id=? AND role='admin'");
            $stmt->bind_param("ssssi", $username, $full_name, $email, $nic_number, $admin_id);

        }
        if ($stmt->execute()) {
            $_SESSION['message'] = "Admin updated successfully!";
            header("Location: manage_admins.php");
            exit;
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
            header("Location: manage_admins.php" . ($admin_id ? "?edit=$admin_id" : ""));
            exit;
        }
    } else {
        // Insert new admin
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email, nic_number) VALUES (?, ?, 'admin', ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $hashedPassword, $full_name, $email, $nic_number);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Admin created successfully!<br>Username: <b>$username</b><br>Temporary Password: <b>$plainPassword</b>";
            header("Location: manage_admins.php");
            exit;
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
            header("Location: manage_admins.php");
            exit;
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='admin'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Admin deleted successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
    header("Location: manage_admins.php");
    exit;
}

// Prefill edit form
$edit_admin = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='admin'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 1) $edit_admin = $res->fetch_assoc();
}

// Handle search
$search_id = $_GET['search_id'] ?? '';

// Fetch all admins with optional search
$admins = [];
$sql = "SELECT * FROM users WHERE role='admin'";
if ($search_id) {
    $sql .= " AND CAST(id AS CHAR) LIKE ?";
    $stmt = $conn->prepare($sql);
    $like_id = "%$search_id%";
    $stmt->bind_param("s", $like_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $sql .= " ORDER BY id DESC";
    $res = $conn->query($sql);
}
while ($row = $res->fetch_assoc()) $admins[] = $row;
?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Manage Admins - University Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/global_tables.css">
<style>
body { font-family: 'Roboto', sans-serif; background-color: #f4f6f9; margin:0; }
.navbar { background-color: #004080; color: white; padding: 15px 30px; font-size: 18px; font-weight: 500; margin-bottom: 20px;}
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
.search-bar button:hover { background: #0066cc; }
</style>
</head>
<body>
<div class="navbar">University Portal - Manage Admins</div>
<div class="container">

<div style="margin-bottom: 20px;">
    <a href="dashboard.php" class="btn btn-edit">← Back to Dashboard</a>
</div>

<?php if (!empty($message)): ?><p class="success"><?= $message ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="error"><?= $error ?></p><?php endif; ?>

<div class="card">
<h3><?= $edit_admin ? 'Edit Admin' : 'Add New Admin' ?></h3>
<form method="POST" action="">
    <?php if ($edit_admin): ?>
        <input type="hidden" name="admin_id" value="<?= $edit_admin['id'] ?>">
    <?php endif; ?>
    <label>Full Name:</label>
    <input type="text" name="full_name" value="<?= htmlspecialchars($edit_admin['full_name'] ?? ($_POST['full_name'] ?? '')) ?>" required>

    <label>Username (unique):</label>
    <input type="text" name="username" value="<?= htmlspecialchars($edit_admin['username'] ?? ($_POST['username'] ?? '')) ?>" required>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($edit_admin['email'] ?? ($_POST['email'] ?? '')) ?>" required>

    <label>NIC Number:</label>
    <input type="text" name="nic_number" value="<?= htmlspecialchars($edit_admin['nic_number'] ?? ($_POST['nic_number'] ?? '')) ?>" required>

    <label>Password (optional, leave blank to auto-generate or keep existing):</label>
    <input type="text" name="password" value="">

    <button type="submit" class="btn btn-add"><?= $edit_admin ? 'Update Admin' : 'Create Admin' ?></button>
    <?php if ($edit_admin): ?>
        <a href="manage_admins.php" class="btn btn-edit">Cancel</a>
    <?php endif; ?>
</form>
</div>

<div class="card">
<div class="search-bar-container">
    <h3>All Admins</h3>
    <form method="GET" class="search-bar" action="manage_admins.php">
        <input type="number" name="search_id" placeholder="Enter Admin ID" value="<?= htmlspecialchars($search_id) ?>">
        <button type="button" onclick="window.location.href='manage_admins.php'" 
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
                <th>NIC Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($admins as $a): ?>
            <tr>
                <td><?= $a['id'] ?></td>
                <td><?= htmlspecialchars($a['username']) ?></td>
                <td><?= htmlspecialchars($a['full_name']) ?></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td><?= htmlspecialchars($a['nic_number']) ?></td>
                <td>
                    <a class="btn btn-edit" href="manage_admins.php?edit=<?= $a['id'] ?>">Edit</a>
                    <button type="button" class="btn btn-delete" data-id="<?= $a['id'] ?>">Delete</button>
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
