<?php
session_start();
require '../includes/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// Fetch user details from DB
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userDetails = $result->fetch_assoc();
$stmt->close();

// Handle Profile Update
if (isset($_POST['update_profile'])) {

    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $nic_number = $_POST['nic_number'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $address = $_POST['address'] ?? '';

    // Handle profile image upload
    $profile_image_name = '';
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = __DIR__ . "/../uploads/"; // goes one level up to root
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $imageFileType = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $newFileName = "user_{$userId}_" . time() . "." . $imageFileType;

        $allowedTypes = ['jpg','jpeg','png','gif'];

        // ❌ Reject invalid formats
        if (!in_array($imageFileType, $allowedTypes)) {
            $_SESSION['message'] = "Invalid image format! Only JPG, JPEG, PNG, GIF are allowed.";
            $_SESSION['message_type'] = "danger";
            header("Location: dashboard.php");
            exit;
        }

        // ✔ Move file to uploads folder
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetDir . $newFileName)) {
            $profile_image_name = $newFileName;
        } else {
            $_SESSION['message'] = "Failed to upload image!";
            $_SESSION['message_type'] = "danger";
            header("Location: dashboard.php");
            exit;
        }
    }


    // Fetch existing image to keep if no new upload
    $res = $conn->query("SELECT profile_image FROM users WHERE id=$userId");
    $existing = $res->fetch_assoc();
    if (empty($profile_image_name) && !empty($existing['profile_image'])) {
        $profile_image_name = $existing['profile_image'];
    }

    // Update user in database
    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, mobile=?, nic_number=?, date_of_birth=?, address=?, profile_image=? WHERE id=?");
    $stmt->bind_param("sssssssi", $full_name, $email, $mobile, $nic_number, $date_of_birth, $address, $profile_image_name, $userId);
    $stmt->execute();
    
    // Optional: Save data to database here if you want
    $_SESSION['message'] = "Profile saved successfully!";
    header("Location: dashboard.php");
    exit;
}

// =================== ADMIN STATS ===================
$subjectCount = $studentCount = $timetableCount = $adminCount = $notificationCount = 0;

if ($role === 'admin') {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM subjects_master");
    $subjectCount = $res ? intval($res->fetch_assoc()['cnt']) : 0;

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='student'");
    $studentCount = $res ? intval($res->fetch_assoc()['cnt']) : 0;

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM offered_subjects");
    $timetableCount = $res ? intval($res->fetch_assoc()['cnt']) : 0;

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='admin'");
    $adminCount = $res ? intval($res->fetch_assoc()['cnt']) : 0;

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM notifications");
    $notificationCount = $res ? intval($res->fetch_assoc()['cnt']) : 0;
}

// =================== STUDENT STATS ===================
$registeredSubjects = $availableSubjects = $unreadNotifications = 0;

if ($role === 'student') {
    // Count registered subjects for this student
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM registrations WHERE user_id = $userId");
    $registeredSubjects = $res ? intval($res->fetch_assoc()['cnt']) : 0;

    // Fetch student's faculty and study level first
    $stmt = $conn->prepare("SELECT faculty_id, study_level_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $faculty_id = $user['faculty_id'];
    $study_level_id = $user['study_level_id'];
    $stmt->close();

    // Map numeric study level ID to string
    $level_map = [
        1 => "Diploma",
        2 => "Bachelor",
        3 => "Master"
    ];
    $study_level = $level_map[$study_level_id];

    // Count all offered subjects for this student's faculty & study level
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM offered_subjects os
        JOIN subjects_master sm ON os.subject_id = sm.id
        WHERE sm.faculty_id = ? AND os.study_level = ?
    ");
    $stmt->bind_param("is", $faculty_id, $study_level);
    $stmt->execute();
    $res = $stmt->get_result();
    $availableSubjects = $res ? intval($res->fetch_assoc()['cnt']) : 0;
    $stmt->close();

    // Count unread notifications
    $res = $conn->query("
        SELECT COUNT(*) AS cnt
        FROM notifications n
        WHERE n.id NOT IN (
            SELECT notification_id
            FROM notifications_read
            WHERE user_id = $userId
        )
    ");
    $unreadNotifications = $res ? intval($res->fetch_assoc()['cnt']) : 0;
}


// User details for Profile Modal
$res = $conn->query("SELECT * FROM users WHERE id=$userId");
$userDetails = $res->fetch_assoc() ?? [];

?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - University Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin:0; padding:0; font-family:'Roboto',sans-serif; }
body { background: #f4f6f9; }

/* Navbar */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #004080;
    padding: 15px 30px;
    border-radius: 5px;
    color: #fff;
    margin-bottom: 30px;
}
.navbar h2 { font-weight: 500; font-size: 24px; }

/* User Dropdown */
.navbar .user-dropdown { position: relative; cursor: pointer; }
.navbar .user-dropdown .user-name { font-weight: 500; }
.navbar .user-dropdown .dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #fff;
    min-width: 160px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    border-radius: 6px;
    overflow: hidden;
    z-index: 1000;
}
.navbar .user-dropdown .dropdown-content a {
    color: #333;
    padding: 10px 15px;
    text-decoration: none;
    display: block;
    font-size: 14px;
}
.navbar .user-dropdown .dropdown-content a:hover { background-color: #f1f1f1; }
.navbar .user-dropdown:hover .dropdown-content { display: block; }

/* Admin/Student Header */
.header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding: 0 10px;
}
h2.section-title { font-size: 24px; color: #004080; border-bottom:2px solid #004080; padding-bottom:6px; }
.panel-info { font-size: 14px; color: #555; margin-top: 4px; }

/* Dashboard Grid */
.dashboard { display:flex; flex-wrap:wrap; gap:20px; padding:0 10px; justify-content:flex-start ; }
.card {
    flex: 0 1 calc(33.333% - 20px);
    min-height:140px;
    border-radius:12px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    padding:20px;
    color:#fff;
    text-decoration:none;
    font-weight:500;
    position:relative;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    transition:all 0.3s ease;
    background:#2ecc71;
}
.card:hover { transform:translateY(-6px); box-shadow:0 10px 22px rgba(0,0,0,0.2); }
.card .icon { font-size:32px; margin-bottom:10px; }
.card .title { font-size:18px; margin-bottom:4px; font-weight:600; }
.card .main-stat { font-size:28px; font-weight:700; margin-bottom:4px; }
.card .metric { font-size:14px; font-weight:400; }

/* Modal */
.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
.modal-content { background:#fff; padding:20px; border-radius:8px; width:90%; max-width:400px; position:relative; }
.modal-content h3 { margin-bottom:15px; }
.modal-content label { display:block; margin-bottom:5px; margin-top:10px; }
.modal-content input, .modal-content textarea { width:100%; padding:6px 8px; border-radius:4px; border:1px solid #ccc; }
.modal-content button { margin-top:15px; padding:6px 12px; border:none; border-radius:5px; background:#28a745; color:#fff; cursor:pointer; }
.modal-close { position:absolute; top:10px; right:12px; cursor:pointer; font-weight:bold; font-size:18px; }

.modal-content {
    background:#fff; 
    padding:20px; 
    border-radius:8px; 
    width:90%; 
    max-width:400px; 
    max-height:90vh; /* Limit height for scroll */
    overflow-y:auto;   /* Scroll if content exceeds height */
    position:relative;
}
.modal-content::-webkit-scrollbar {
    width:6px;
}
.modal-content::-webkit-scrollbar-thumb {
    background: #004080;
    border-radius:3px;
}
.modal-content h3 {
    margin-bottom:15px;
    text-align:center;
    color:#004080;
}
.modal-content label {
    display:block; 
    margin-bottom:5px; 
    margin-top:10px; 
    font-weight:500;
}
.modal-content input, 
.modal-content textarea {
    width:100%; 
    padding:6px 8px; 
    border-radius:4px; 
    border:1px solid #ccc; 
    margin-bottom:5px;
}
.modal-content button {
    width:100%;
    margin-top:15px;
    padding:10px; 
    border:none; 
    border-radius:5px; 
    background:#28a745; 
    color:#fff; 
    cursor:pointer;
    font-weight:600;
    font-size:16px;
}

.success { color: green; font-weight: 500; margin-bottom: 10px; }
.error { color: red; font-weight: 500; margin-bottom: 10px; }

/* Responsive */
@media(max-width:768px) {
    .header-row { flex-direction: column; align-items:flex-start; }
    .dashboard { flex-direction: column; padding:0 10px; }
    .card { flex:1 1 100%; }
}
</style>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script>
// Modal
function openProfile() { document.getElementById('profileModal').style.display='flex'; }
function closeProfile() { document.getElementById('profileModal').style.display='none'; }
</script>
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <h2>University Portal</h2>
    </div>
    <div class="nav-right">
        <div class="user-dropdown">
            <span class="user-name">Welcome, <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>) &#9662;</span>
            <div class="dropdown-content">
                <a href="javascript:void(0)" onclick="openProfile()">Profile</a>
                <a href="change_password.php">Change Password</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>

<?php if (isset($_SESSION['message'])): ?>
    <?php 
        $type = $_SESSION['message_type'] ?? 'success'; 
        $class = ($type === 'danger') ? 'error' : 'success'; 
    ?>
    <p class="<?= $class ?>" style="text-align:center; margin:10px 0;">
        <?= htmlspecialchars($_SESSION['message']) ?>
    </p>
    <?php 
        unset($_SESSION['message']); 
        unset($_SESSION['message_type']); 
    ?>
<?php endif; ?>


<div class="header-row">
    <div>
        <h2 class="section-title"><?= $role==='admin'?'Admin Panel':'Student Panel' ?></h2>
        <p class="panel-info">Data updated as of <span id="current-time"></span></p>
    </div>
</div>

<div class="dashboard">
<?php if($role === 'admin'): ?>
    <a href="manage_subjects.php" class="card">
        <div class="icon"><i class="fas fa-book"></i></div>
        <div class="title">Manage Subjects</div>
        <div class="main-stat"><?= $subjectCount ?></div>
        <div class="metric">Total Subjects</div>
    </a>
    <a href="manage_offered_subjects.php" class="card">
        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="title">Manage Offered Subjects</div>
        <div class="main-stat"><?= $timetableCount ?></div>
        <div class="metric">Total Schedule Entries</div>
    </a>
    <a href="manage_admins.php" class="card">
        <div class="icon"><i class="fas fa-user-shield"></i></div>
        <div class="title">Manage Admins</div>
        <div class="main-stat"><?= $adminCount ?></div>
        <div class="metric">Total Admins</div>
    </a>
    <a href="manage_students.php" class="card">
        <div class="icon"><i class="fas fa-user-graduate"></i></div>
        <div class="title">Manage Students</div>
        <div class="main-stat"><?= $studentCount ?></div>
        <div class="metric">Total Students</div>
    </a>
    <a href="notifications.php" class="card">
        <div class="icon"><i class="fas fa-bell"></i></div>
        <div class="title">Manage Notifications</div>
        <div class="main-stat">
            <?= $notificationCount ?>
        </div>
        <div class="metric">Total Announcements</div>
    </a>

<?php elseif($role === 'student'): ?>
    <a href="manage_timetable.php" class="card">
        <div class="icon"><i class="fas fa-book-open"></i></div>
        <div class="title">View Offered Subjects</div>
        <div class="main-stat"><?= $availableSubjects ?></div>
        <div class="metric">Available Subjects</div>
    </a>
    <a href="my_timetable.php" class="card">
        <div class="icon"><i class="fas fa-calendar-check"></i></div>
        <div class="title">My Timetable</div>
        <div class="main-stat"><?= $registeredSubjects ?></div>
        <div class="metric">Total Registered Subjects</div>
    </a>
    <a href="notifications.php" class="card">
        <div class="icon"><i class="fas fa-bell"></i></div>
        <div class="title">Notifications</div>
        <div class="main-stat">
            <?= $unreadNotifications ?>
        </div>
        <div class="metric">Unread Notifications</div>
    </a>

<?php endif; ?>
</div>


<!-- Profile Modal -->
<div class="modal" id="profileModal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeProfile()">&times;</span>
        <h3>Edit Profile</h3>

        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
            <!-- Profile Picture Display -->
            <div style="text-align:center; margin-bottom:15px;">
                <?php if (!empty($userDetails['profile_image'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($userDetails['profile_image']) ?>" 
                         alt="Profile Image" 
                         style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:2px solid #004080; margin-bottom:10px;">
                <?php else: ?>
                    <img src="https://via.placeholder.com/120" 
                         alt="Profile Image" 
                         style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:2px solid #004080; margin-bottom:10px;">
                <?php endif; ?>
            </div>

            <!-- Upload Image -->
            <label>Upload Profile Image</label>
            <input type="file" name="profile_image" accept="image/*" style="margin-bottom:15px;">

            <!-- User Details -->
            <label>Username (cannot change)</label>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" readonly>

            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($userDetails['full_name'] ?? '') ?>" readonly>

            <label>Email</label>
            <input type="email" name="email" 
                value="<?= htmlspecialchars($userDetails['email'] ?? '') ?>" readonly>


            <label>Mobile</label>
            <input type="text" name="mobile" value="<?= htmlspecialchars($userDetails['mobile'] ?? '') ?>">

            <?php if($role === 'student'): ?>
                <label>Student ID (cannot change)</label>
                <input type="text" name="student_id" value="<?= htmlspecialchars($userDetails['id']) ?>" readonly>
            <?php else: ?>
                <label>Official ID (cannot change)</label>
                <input type="text" name="official_id" value="<?=htmlspecialchars($userDetails['id']) ?>" readonly>
            <?php endif; ?>

            <label>NIC Number</label>
            <input type="text" name="nic_number" value="<?= htmlspecialchars($userDetails['nic_number'] ?? '') ?>" readonly>

            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" value="<?= htmlspecialchars($userDetails['date_of_birth'] ?? '') ?>">

            <label>Address</label>
            <textarea name="address" style="resize: vertical; min-height:60px;"><?= htmlspecialchars($userDetails['address'] ?? '') ?></textarea>

            <button type="submit" name="update_profile" style="margin-top:15px;">Save Changes</button>
        </form>
    </div>
</div>

<script>
// Function to update time
function updateTime() {
    const now = new Date();
    const options = {
        month: 'long', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit', hour12: true 
    };
    document.getElementById('current-time').textContent = now.toLocaleString('en-US', options);
}

// Initial call and update every second
updateTime();
setInterval(updateTime, 1000);
</script>

</body>
</html>
