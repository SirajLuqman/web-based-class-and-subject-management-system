<?php
require '../includes/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['username'] ?? ''); // username OR email
    $password = $_POST['password'] ?? '';

    if ($loginInput === '' || $password === '') {
        $error = 'Please fill in both fields.';
    } else {

        // Allow login using username OR email
        $stmt = $conn->prepare("
            SELECT * FROM users 
            WHERE username = ? OR email = ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $loginInput, $loginInput);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {

            // Set session values
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Check if first login using temporary password
            if ($user['first_login'] == 1) {
                header('Location: change_password.php');
                exit;
            } else {
                header('Location: dashboard.php');
                exit;
            }

        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}

?>

<?php include '../includes/header.php'; ?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - University Portal</title>
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

/* Login form container */
.login-container {
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

.login-container img {
    width: 120px;
    height: auto;
    margin-bottom: 15px;
    animation: logoBounce 1.5s infinite;
}
@keyframes logoBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.login-container h2 {
    color: #004080;
    margin-bottom: 20px;
}
.login-container input {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}
.login-container button {
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
.login-container button:hover { background-color: #00264d; }
.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 14px;
    color: #004080;
}
.forgot-password {
    display: block;
    margin-top: 10px;
    font-size: 14px;
    color: #004080;
    text-decoration: none;
}
.forgot-password:hover { text-decoration: underline; }
.error-message {
    position: relative;  /* relative to the form container */
    width: 100%;
    text-align: center;
    color: #c0392b;
    font-size: 14px;
    padding: 2px 0;
    margin-bottom: 10px;  /* space between error and input fields */
    opacity: 1;
    transition: opacity 0.6s ease, transform 0.6s ease;
    background: rgba(255,255,255,0.2); /* subtle */
    border-radius: 4px;
}

</style>
</head>

<body>

<!-- Particle Background -->
<div id="particles-js" style="position:absolute; width:100%; height:100%; top:0; left:0; z-index:1;"></div>

<div class="login-container" style="position: relative; z-index:5;">

    <img src="/class_scheduler/assets/images/logo.png" 
         alt="Logo" 
         style="width:200px; height:auto;">

    <h2>University Portal Login</h2>

    <?php if($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div style="position: relative;">
            <input type="text" name="username" placeholder="Username or Email" required>
        </div>

        <div style="position: relative;">
            <input type="password" id="password" name="password" placeholder="Password" required>
            <span class="toggle-password" onclick="togglePassword()">Show</span>
        </div>

        <button type="submit">Login</button>
    </form>

    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
// Initialize particles
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
function togglePassword() {
    const pw = document.getElementById('password');
    const toggle = document.querySelector('.toggle-password');
    if (pw.type === 'password') { pw.type='text'; toggle.textContent='Hide'; }
    else { pw.type='password'; toggle.textContent='Show'; }
}

setTimeout(function(){
    const err = document.querySelector('.error-message');
    if(err){
        err.style.opacity = '0';
        err.style.transform = 'translateY(-10px)';
        setTimeout(() => err.remove(), 600); // remove after fade
    }
}, 3000); // 3 seconds display

</script>

</body>


</html>
