<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Class Scheduler - Welcome</title>
<!-- Redirect to login after 3 seconds -->
<meta http-equiv="refresh" content="3;url=pages/login.php">

<style>
/* Reset */
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Roboto', sans-serif; }

/* Fullscreen body */
body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    background: linear-gradient(-45deg, #2ecc71, #27ae60, #16a085, #1abc9c);
    background-size: 400% 400%;
    animation: gradientBG 10s ease infinite;
    overflow: hidden;
    color: white;
}

/* Gradient background animation */
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

/* Splash content */
.splash-content {
    position: relative;
    z-index: 2;
    text-align: center;
}

/* Logo */
.logo {
    width: 120px;
    height: 120px;
    margin-bottom: 20px;
    animation: logoBounce 1.5s infinite;
}

@keyframes logoBounce {
    0%, 100% { transform: translateY(0);}
    50% { transform: translateY(-15px);}
}

/* Welcome text */
h1 {
    font-size: 2.2rem;
    margin-bottom: 10px;
    letter-spacing: 1px;
}

/* Typing effect */
.typing {
    font-size: 1.1rem;
    color: #e0f7e0;
    min-height: 24px;
}

/* Progress bar */
.progress-bar-container {
    width: 200px;
    height: 8px;
    background: rgba(255,255,255,0.3);
    border-radius: 4px;
    margin-top: 20px;
    overflow: hidden;
    margin-left:auto;
    margin-right:auto;
}

.progress-bar {
    width: 0%;
    height: 100%;
    background: #ffffff;
    border-radius: 4px;
    animation: progressLoad 2s forwards;
}

@keyframes progressLoad {
    0% { width: 0%; }
    100% { width: 100%; }
}

/* Responsive */
@media(max-width:480px){
    h1 { font-size:1.8rem;}
    .logo { width:90px; height:90px; }
    .progress-bar-container { width:150px;}
}
</style>
</head>
<body>

<!-- Particle Background -->
<div id="particles-js"></div>

<div class="splash-content">
    <!-- Logo -->
    <img src="assets/images/logo.png" alt="Logo" class="logo" style="width:300px; height:auto;">

    <!-- Welcome text -->
    <h1>Welcome to University Portal</h1>

    <!-- Typing tagline -->
    <div class="typing" id="typing"></div>

    <!-- Progress bar -->
    <div class="progress-bar-container">
        <div class="progress-bar"></div>
    </div>
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

// Typing effect
const text = "Smart Academic Management!";
let index = 0;
function typeText(){
    if(index < text.length){
        document.getElementById('typing').innerHTML += text.charAt(index);
        index++;
        setTimeout(typeText, 50);
    }
}
typeText();
</script>

</body>

</html>
