<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ================== SECURITY FUNCTIONS ==================
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function validateInput($data, $maxLength = 1000) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    if (strlen($data) > $maxLength) return false;
    return $data;
}

function logError($message) {
    error_log("Notification System Error: " . $message);
}

$csrfToken = generateCsrfToken();
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// ================== AJAX HANDLE: MARK READ (Student) ==================
if ($role === 'student' && isset($_POST['ajax_mark_read'])) {
    header('Content-Type: application/json');
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'CSRF token validation failed']);
        exit;
    }
    if (!isset($_POST['notification_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Notification ID required']);
        exit;
    }

    $nid = intval($_POST['notification_id']);
    if ($nid <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid notification ID']);
        exit;
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO notifications_read (user_id, notification_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $nid);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        logError("Mark read failed: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    $stmt->close();
    exit; 
}

// ================== ADMIN: ADD NOTIFICATION ==================
if ($role === 'admin' && isset($_POST['add_notification'])) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Security token validation failed";
        header("Location: notifications.php");
        exit;
    }

    $title = validateInput($_POST['title'] ?? '', 255);
    $message = validateInput($_POST['message'] ?? '', 2000);

    if (!$title || !$message) {
        $_SESSION['error'] = "Invalid input or too long";
        header("Location: notifications.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO notifications (title, message, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $title, $message);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Notification published successfully!";
    } else {
        logError("Add notification failed: " . $stmt->error);
        $_SESSION['error'] = "Failed to publish notification. Please try again.";
    }
    $stmt->close();
    header("Location: notifications.php");
    exit;
}

// ================== ADMIN: DELETE NOTIFICATION ==================
if ($role === 'admin' && isset($_GET['delete'])) {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['error'] = "Security token missing";
        header("Location: notifications.php");
        exit;
    }

    $nid = intval($_GET['delete']);
    if ($nid <= 0) {
        $_SESSION['error'] = "Invalid notification ID";
        header("Location: notifications.php");
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("DELETE FROM notifications_read WHERE notification_id = ?");
        $stmt1->bind_param("i", $nid);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt2->bind_param("i", $nid);
        $stmt2->execute();
        if ($stmt2->affected_rows > 0) {
            $conn->commit();
            $_SESSION['message'] = "Notification deleted successfully!";
        } else {
            $conn->rollback();
            $_SESSION['error'] = "Notification not found or already deleted";
        }
        $stmt2->close();
    } catch (Exception $e) {
        $conn->rollback();
        logError("Delete notification failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to delete notification. Please try again.";
    }

    header("Location: notifications.php");
    exit;
}

// ================== LOAD NOTIFICATIONS ==================
try {
    if ($role === 'student') {
        $stmt = $conn->prepare("
            SELECT n.id, n.title, n.message, n.created_at,
            CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END AS is_read
            FROM notifications n
            LEFT JOIN notifications_read nr 
              ON n.id = nr.notification_id AND nr.user_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $conn->prepare("SELECT id, title, message, created_at, 1 AS is_read FROM notifications ORDER BY created_at DESC");
    }
    $stmt->execute();
    $notifications = $stmt->get_result();
    $stmt->close();
} catch (Exception $e) {
    logError("Load notifications failed: " . $e->getMessage());
    $notifications = false;
    $_SESSION['error'] = "Failed to load notifications";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - University Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<style>
body { font-family: 'Roboto', sans-serif; background: #f4f6f9; margin:0; }
.navbar {
    background-color: #004080;
    color: white;
    padding: 15px 30px;
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 20px;
}
.container { width: 95%; max-width: 1000px; margin: 0 auto 40px; }
.back-btn {
    display: inline-block;
    background-color: #ffc107;
    color: #fff;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 20px;
    border: none;
}
.back-btn:hover { background-color: #004080; }

.card { background-color: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
input, textarea { width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #ccc; }
.publish-btn { background-color: #28a745; color: white; padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; font-weight: 500; margin-top: 10px; }

.notif-grid { display:flex; flex-direction:column; gap:15px; }
.notification-card { 
    background:#fff; padding:15px; border-radius:6px; border-left:6px solid #004080; cursor:pointer; 
    box-shadow:0 2px 5px rgba(0,0,0,0.05); position:relative; transition: transform 0.2s;
}
.notification-card:hover { transform: translateX(5px); box-shadow:0 4px 12px rgba(0,0,0,0.1); }
.unread { border-left-color:#e67e22; background:#fffbf2; }
.unread::after { content: "NEW"; position:absolute; top:15px; right:15px; background:#e67e22; color:#fff; font-size:10px; padding:2px 5px; border-radius:3px; font-weight:bold; }

.title { font-weight:bold; color:#004080; font-size:16px; margin-bottom:4px; }
.date { font-size:12px; color:#777; margin-bottom:6px; }
.preview { font-size:14px; color:#555; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.delete-btn { display:inline-block; background:#dc3545; color:#fff; padding:5px 10px; border-radius:4px; text-decoration:none; font-size:12px; margin-top:10px; }

.modal {
    display:none; position:fixed; z-index:2000; left:0; top:0; width:100%; height:100%;
    background:rgba(0,0,0,0.5); justify-content:center; align-items:center;
    opacity:0; transition: opacity 0.25s ease-in-out;
}
.modal.show { opacity:1; }
.modal-content { background:#fff; padding:30px 40px; border-radius:8px; width:90%; max-width:600px; max-height:80vh; overflow-y:auto; position:relative; transform:translateY(-20px); transition: transform 0.25s ease; }
.modal.show .modal-content { transform:translateY(0); }
.modal-close { position:absolute; top:15px; right:20px; cursor:pointer; font-size:24px; color:#aaa; }
.modal-title { font-size: 20px; font-weight:bold; color:#004080; margin-bottom:5px; }
.modal-date { font-size:12px; color:#777; margin-bottom:15px; display:block; border-bottom:1px solid #eee; padding-bottom:8px; }
.modal-body { font-size:16px; color:#333; line-height:1.6; white-space: pre-wrap; }

.alert { padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; font-weight: 500; }
.alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<script>
const csrfToken = '<?= $csrfToken ?>';

function openNotification(card) {
    const id = card.getAttribute('data-id');
    const title = card.getAttribute('data-title');
    const date = card.getAttribute('data-date');
    const message = card.getAttribute('data-message');
    const isRead = card.getAttribute('data-read');
    const role = '<?= $role ?>';

    const modal = document.getElementById('notifModal');
    modal.style.display = 'flex';
    modal.classList.add('show');

    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalDate').innerText = date;
    document.getElementById('modalBody').innerText = message;

    if(role==='student' && isRead=='0'){
        card.classList.remove('unread');
        card.setAttribute('data-read','1');

        const formData = new FormData();
        formData.append('ajax_mark_read','1');
        formData.append('notification_id', id);
        formData.append('csrf_token', csrfToken);

        fetch('notifications.php',{ method:'POST', body:formData })
        .then(r=>r.json())
        .then(data=>{ if(data.status!=='success') console.error('Failed to mark read'); })
        .catch(e=>console.error('Error marking read:', e));
    }
}

function closeModal(){
    const modal = document.getElementById('notifModal');
    modal.classList.remove('show');
    setTimeout(()=>{ modal.style.display='none'; }, 250);
}

window.onclick = function(e){ if(e.target==document.getElementById('notifModal')) closeModal(); }
window.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModal(); });

function confirmDelete(element){
    event.stopPropagation();
    const confirmed = confirm('Are you sure you want to delete this notification? This action cannot be undone.');
    if(confirmed){
        const url = new URL(element.href);
        url.searchParams.append('csrf_token', csrfToken);
        window.location.href = url.toString();
    }
    return false;
}
</script>
</head>
<body>
<div class="navbar">University Portal - Manage Notifications</div>
<div class="container">
    <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> ← Back to Dashboard</a>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if($role==='admin'): ?>
    <div class="card">
        <h3>Create Notification</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <label>Title</label>
            <input type="text" name="title" required maxlength="255">
            <label>Message</label>
            <textarea name="message" rows="4" required maxlength="2000"></textarea>
            <button class="publish-btn" name="add_notification">Publish</button>
        </form>
    </div>
    <?php endif; ?>

    <h3>All Notifications</h3>
    <div class="notif-grid">
        <?php if($notifications && $notifications->num_rows>0): ?>
            <?php while($n=$notifications->fetch_assoc()): ?>
                <?php $dateFormatted=date('F j, Y \a\t g:i A', strtotime($n['created_at'])); ?>
                <div class="notification-card <?= $n['is_read']==0?'unread':'' ?>"
                     data-id="<?= $n['id'] ?>"
                     data-title="<?= htmlspecialchars($n['title'], ENT_QUOTES) ?>"
                     data-date="<?= $dateFormatted ?>"
                     data-message="<?= htmlspecialchars($n['message'], ENT_QUOTES) ?>"
                     data-read="<?= $n['is_read'] ?>"
                     onclick="openNotification(this)">
                    <div class="title"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="date"><?= $dateFormatted ?></div>
                    <div class="preview"><?= htmlspecialchars(substr($n['message'],0,80)) ?>... <span style="font-weight:bold;color:#004080">(Click to read)</span></div>
                    <?php if($role==='admin'): ?>
                        <a href="?delete=<?= $n['id'] ?>&csrf_token=<?= $csrfToken ?>" class="delete-btn" onclick="return confirmDelete(this);">Delete</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center;color:#777;"><?= $notifications===false?'Error loading notifications':'No notifications found.' ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div id="notifModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div id="modalTitle" class="modal-title"></div>
        <span id="modalDate" class="modal-date"></span>
        <div id="modalBody" class="modal-body"></div>
        <div style="text-align:right; margin-top:20px;">
            <button onclick="closeModal()" style="background:#004080;color:#fff;padding:8px 16px;border:none;border-radius:4px;cursor:pointer;">Close</button>
        </div>
    </div>
</div>
</body>
</html>
