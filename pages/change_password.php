<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$showLoginButton = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $message = "Passwords do not match!";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password=?, first_login=0 WHERE username=?");
        $stmt->bind_param("ss", $hashed, $_SESSION['username']);
        $stmt->execute();

        session_destroy();
        $message = "Password changed successfully!";
        $showLoginButton = true;
    }
}
?>

<?php include '../includes/header.php'; ?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password - University Portal</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Roboto', sans-serif; }

/* Fullscreen body with animated gradient */
body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    background: linear-gradient(-45deg, #2ecc71, #27ae60, #16a085, #1abc9c);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;
    overflow: hidden;
    color: #fff;
}
@keyframes gradientBG {
    0% {background-position:0% 50%;}
    50% {background-position:100% 50%;}
    100% {background-position:0% 50%;}
}

/* Particle canvas */
#particles-js {
    position: absolute;
    width: 100%;
    height: 100%;
    top:0;
    left:0;
    z-index: 1;
}

/* Change Password container */
.change-password-container {
    position: relative;
    z-index: 2;
    background: rgba(255,255,255,0.75); /* same as login */
    padding: 60px 45px;
    border-radius: 12px;
    width: 100%;
    max-width: 380px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    text-align: center;
    overflow-y: auto;
}

.change-password-container img {
    width: 120px;
    height: auto;
    margin-bottom: 20px;
    animation: logoBounce 1.5s infinite;
}
@keyframes logoBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.change-password-container h2 {
    color: #004080;
    margin-bottom: 20px;
}

.change-password-container input {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.change-password-container button {
    width: 100%;
    padding: 12px;
    margin-top: 15px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    background-color: #004080;
    color: #fff;
    cursor: pointer;
    transition: 0.3s;
}
.change-password-container button:hover { background-color: #00264d; }

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 14px;
    color: #004080;
}
.message {
    background: #e0f7e0;
    color: #004080;
    padding: 10px 12px;
    border-radius: 5px;
    font-size: 14px;
    margin-bottom: 15px;
    text-align: center;
}

a.login-link {
    display: block;
    margin-top: 10px;
    font-size: 14px;
    color: #004080;
    text-decoration: none;
}
a.login-link:hover { text-decoration: underline; }


</style>
</head>
<body>

<!-- Particle Background -->
<div id="particles-js"></div>

<div class="change-password-container">

    <img src="/class_scheduler/assets/images/logo.png" 
         alt="Logo" 
         style="width:200px; height:auto;">

    <h2>Create New Password</h2>

    <?php if($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php if($showLoginButton): ?>
            <a href="login.php" class="login-link">Go to Login</a>
        <?php endif; ?>
    <?php endif; ?>

    <?php if(!$showLoginButton): ?>
    <form method="post">
        <div style="position: relative;">
            <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
            <span class="toggle-password" onclick="togglePassword('new_password', this)">Show</span>
        </div>
        <div style="position: relative;">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            <span class="toggle-password" onclick="togglePassword('confirm_password', this)">Show</span>
        </div>
        <button type="submit">Save Password</button>
        <br>
        <a href="javascript:history.back()" class="login-link" style="margin-top:10px;">← Back</a>
    </form>
    <?php endif; ?>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
// Particles animation
particlesJS('particles-js', {
    "particles": {
        "number": {"value": 40,"density":{"enable":true,"value_area":800}},
        "color": {"value":"#ffffff"},
        "shape": {"type":"circle"},
        "opacity": {"value":0.3,"random":true},
        "size": {"value":3,"random":true},
        "line_linked": {"enable":true,"distance":150,"color":"#ffffff","opacity":0.1,"width":1},
        "move": {"enable":true,"speed":2,"direction":"none","random":true,"straight":false,"bounce":false}
    },
    "interactivity": {
        "detect_on":"canvas",
        "events": {"onhover":{"enable":true,"mode":"grab"},"onclick":{"enable":true,"mode":"push"}}
    },
    "retina_detect": true
});

// Show/hide password
function togglePassword(inputId, elem) {
    const pw = document.getElementById(inputId);
    if(pw.type === 'password') { pw.type='text'; elem.textContent='Hide'; }
    else { pw.type='password'; elem.textContent='Show'; }
}
</script>
</body>
</html>
