<?php
require '../includes/db.php';
session_start();

$error = '';
$success = '';
$tempPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $userId = trim($_POST['user_id'] ?? '');

    if ($username === '' || $email === '' || $userId === '') {
        $error = "Please fill in all fields.";
    } else {
        // Verify user in database
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND email=? AND (id=? OR id=?) LIMIT 1");
        $stmt->bind_param("ssss", $username, $email, $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Generate temporary password
            $tempPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$!'), 0, 8);
            $hashed = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Update password and mark first login
            $update = $conn->prepare("UPDATE users SET password=?, first_login=1 WHERE id=?");
            $update->bind_param("si", $hashed, $user['id']);
            $update->execute();

            // Success message
            $success = "Password reset successfully!<br>";
            $success .= "<strong>Username:</strong> {$user['username']}<br>";
            $success .= "<strong>Temporary Password:</strong> <span class='temp-pass'>{$tempPassword}</span><br>";
            $success .= "Use this password to log in and set a new password.";
        } else {
            $error = "No user found with provided details.";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - University Portal</title>
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
#particles-js { position:absolute; width:100%; height:100%; top:0; left:0; z-index:1; }

/* Forgot Password form container */
.forgot-container {
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

.forgot-container img {
    width: 120px;
    height:auto;
    margin-bottom: 15px;
    animation: logoBounce 1.5s infinite;
}
@keyframes logoBounce {
    0%,100%{ transform: translateY(0);}
    50% { transform: translateY(-10px);}
}

.forgot-container h2 {
    color: #004080;
    margin-bottom: 20px;
}
.forgot-container input {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border-radius:6px;
    border:1px solid #ccc;
    font-size:14px;
}
.forgot-container button {
    width: 100%;
    padding: 12px;
    margin-top: 15px;
    border:none;
    border-radius:6px;
    font-size:16px;
    font-weight:500;
    background-color:#004080;
    color:#fff;
    cursor:pointer;
    transition:0.3s;
}
.forgot-container button:hover { background-color:#00264d; }

.error-message {
    width:100%;
    text-align:center;
    color:#c0392b;
    font-size:14px;
    padding:4px 0;
    margin-bottom:10px;
    background: rgba(255,0,0,0.1);
    border-radius:4px;
}

.success-message {
    width:100%;
    text-align:center;
    color:#155724;
    font-size:14px;
    padding:10px 8px;
    margin-bottom:15px;
    background: rgba(40,167,69,0.2);
    border-radius:6px;
}

.temp-pass {
    display:inline-block;
    background: #27ae60;
    color:#fff;
    padding:4px 8px;
    border-radius:4px;
    font-weight:600;
    margin-top:4px;
}

.back-login {
    display:block;
    margin-top:15px;
    font-size:14px;
    color:#004080;
    text-decoration:none;
}
.back-login:hover { text-decoration:underline; }
</style>
</head>
<body>

<div id="particles-js"></div>

<div class="forgot-container">
    <img src="/class_scheduler/assets/images/logo.png" 
         alt="Logo" 
         style="width:200px; height:auto;">
    <h2>Reset Password</h2>

    <?php if($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="success-message"><?= $success ?></div>
    <?php endif; ?>

    <?php if(!$success): ?>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="user_id" placeholder="Student/Official ID" required>
        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>

    <a href="login.php" class="back-login">Go to Login</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
particlesJS('particles-js',{
    "particles":{"number":{"value":40,"density":{"enable":true,"value_area":800}},
    "color":{"value":"#ffffff"},"shape":{"type":"circle"},"opacity":{"value":0.3,"random":true},
    "size":{"value":3,"random":true},"line_linked":{"enable":true,"distance":150,"color":"#ffffff","opacity":0.1,"width":1},
    "move":{"enable":true,"speed":2,"direction":"none","random":true,"straight":false,"bounce":false}},
    "interactivity":{"detect_on":"canvas","events":{"onhover":{"enable":true,"mode":"grab"},"onclick":{"enable":true,"mode":"push"}}},
    "retina_detect":true
});
</script>

</body>
</html>
