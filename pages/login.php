<?php
session_start();
include '../includes/db.php';

// If already logged in, redirect to homepage
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check for user account
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        // Redirect to homepage after successful login
        header("Location: ../index.php");
        exit();
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* 
         * ==========================================
         *  GLOBAL STYLES & FONTS
         * ==========================================
         */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            min-height: 100vh;
            background-color: #020617; /* Deep Navy Base */
            overflow: hidden;
            padding-top: 0;
            box-sizing: border-box;
            position: relative;
            flex-direction: column;
        }

        /* 
         * ==========================================
         *  BACKGROUND & PARTICLES
         * ==========================================
         */
        /* Ambient Gradient Background */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                linear-gradient(rgba(2, 6, 23, 0.85), rgba(15, 23, 42, 0.9)),
                url('../images/loginpage.jpg');
            background-size: cover;
            background-position: center;
            z-index: -1;
            animation: pulseBackground 10s ease-in-out infinite alternate;
        }

        /* Floating Particles (Pure CSS Stars/Dots) */
        body::after {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                radial-gradient(2px 2px at 50px 100px, rgba(255,255,255,0.5), transparent),
                radial-gradient(2px 2px at 150px 250px, rgba(255,255,255,0.3), transparent),
                radial-gradient(2px 2px at 350px 50px, rgba(255,255,255,0.4), transparent),
                radial-gradient(2px 2px at 550px 450px, rgba(255,255,255,0.2), transparent),
                radial-gradient(2px 2px at 750px 150px, rgba(255,255,255,0.5), transparent);
            background-size: 1000px 1000px;
            z-index: -1;
            animation: floatParticles 60s linear infinite;
            opacity: 0.6;
        }

        @keyframes pulseBackground {
            0% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        @keyframes floatParticles {
            from { background-position: 0 0; }
            to { background-position: 1000px 1000px; }
        }

        /* 
         * ==========================================
         *  LAYOUT & WRAPPERS
         * ==========================================
         */
        .main-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 80px;
            width: 100%;
            max-width: 1200px;
            padding: 20px;
            z-index: 10;
            position: relative;
            margin-bottom: 40px;
        }

        /* 
         * ==========================================
         *  ANIMATED BRAND TEXT
         * ==========================================
         */
        .header-title {
            position: relative;
            margin-bottom: 30px;
            font-size: 4rem;
            font-weight: 800;
            text-align: center;
            width: 100%;
            z-index: 20;
            letter-spacing: -1px;
            white-space: nowrap;
            pointer-events: none; /* Let clicks pass through if it overlaps */
        }

        .header-title span {
            display: inline-block;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInLetter 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            
            /* Gradient Text Effect */
            background: linear-gradient(135deg, #00f2ff 0%, #00c6ff 50%, #9d50bb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(0, 242, 255, 0.3);
        }

        @keyframes fadeInLetter {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 
         * ==========================================
         *  LOGIN CARD (GLASSMORPHISM)
         * ==========================================
         */
        .login-container {
            width: 100%;
            max-width: 500px;
            padding: 30px 40px;
            box-sizing: border-box;
            border-radius: 24px;
            
            /* Glassmorphism Styles */
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            border-left: 1px solid rgba(255, 255, 255, 0.15);
            
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 
                        0 0 0 1px rgba(255, 255, 255, 0.05) inset;
            
            transform-style: preserve-3d;
            animation: floatCard 6s ease-in-out infinite;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 16px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8; /* Match label color for subtlety */
            opacity: 0;
            pointer-events: none;
            transform: translateY(-50%) scale(0.8);
            transition: all 0.2s ease-in-out;
            user-select: none; /* Prevent text selection highlight */
        }

        .toggle-password.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(-50%) scale(1);
        }

        .toggle-password:hover {
            color: #00f2ff; /* Brighten to primary theme color on hover */
        }

        @keyframes floatCard {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h2 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #94a3b8;
            font-size: 0.9rem;
            margin-left: 5px;
        }

        /* 
         * ==========================================
         *  INPUTS & BUTTONS
         * ==========================================
         */
        .login-container input[type="email"],
        .login-container input[type="password"],
        .login-container input[type="text"] {
            width: 100%;
            color: #fff;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 1rem;
            margin-bottom: 16px;
            box-sizing: border-box;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            -webkit-appearance: none;
            appearance: none;
        }

        /* Fix for Chrome/Edge Autofill White Background */
        .login-container input:-webkit-autofill,
        .login-container input:-webkit-autofill:hover, 
        .login-container input:-webkit-autofill:focus, 
        .login-container input:-webkit-autofill:active {
            -webkit-text-fill-color: #ffffff !important;
            transition: background-color 5000s ease-in-out 0s;
            caret-color: white;
        }

        .password-wrapper input {
            margin-bottom: 0;
        }

        .login-container input:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: #00f2ff;
            box-shadow: 0 0 0 4px rgba(0, 242, 255, 0.15);
            outline: none;
            transform: translateY(-1px);
        }
        
        .login-container input::placeholder {
            color: #94a3b8;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            background: linear-gradient(135deg, #00f2ff 0%, #0072ff 100%);
            border: none;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 16px;
            cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(0, 114, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(0, 114, 255, 0.6);
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
        }
        
        button:active {
            transform: translateY(-1px);
        }

        .form-footer {
            text-align: center;
            margin-top: 15px;
        }
        
        .form-footer a {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.3s;
        }
        
        .form-footer a:hover {
            color: #00f2ff !important;
            text-shadow: 0 0 10px rgba(0, 242, 255, 0.5);
        }

        /* 
         * ==========================================
         *  ILLUSTRATION & RESPONSIVENESS
         * ==========================================
         */
        .illustration-box {
            flex: 1;
            max-width: 350px;
            display: none; /* Hidden on small screens */
        }
        
        .illustration-box img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 0 30px rgba(0, 242, 255, 0.2));
            opacity: 0;
            animation: slideDownBag 3s ease-out 0.5s forwards,
                       floatIllustration 6s ease-in-out 3.5s infinite;
        }
        
        @keyframes floatIllustration {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        @keyframes slideDownBag {
            0% {
                opacity: 0;
                transform: translate(-30px, -30px) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translate(0, 0) scale(1);
            }
        }

        /* Error Messages */
        .error-message {
            color:rgba(207, 80, 65, 0.7);
            font-size: 1em;
            text-align: center;
            margin-top: 10px;
        }
        #errorPopup {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(244, 67, 54, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        .error-msg-js {
            color: #ff4757;
            font-size: 0.85rem;
            margin-top: -15px;
            margin-bottom: 15px;
            margin-left: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .main-wrapper {
                flex-direction: column;
                margin-top: 80px;
            }
            .header-title {
                font-size: 2.5rem;
                top: 5%;
            }
            .illustration-box {
                display: none;
            }
        }
        
        @media (min-width: 993px) {
            .illustration-box {
                display: block;
            }
        }
        @media (max-width: 576px) {
            .login-container {
                max-width: 100%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header-title">E-Commerce Store</div>

    <div id="errorPopup" style="display: none;"></div>

    <div class="main-wrapper">
        <!-- Added related picture -->
        <div class="illustration-box">
            <img src="https://cdn-icons-png.flaticon.com/512/1162/1162499.png" alt="Shopping Bag with Items">
        </div>

        <div class="login-container">
            <h2>Login</h2>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <form method="POST">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" autocomplete="off" required>

                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                <button type="submit" name="login">Login</button>
            </form>
            <div class="form-footer">
                <a href="forgot_password.php" style="color: #3498db; text-decoration: none; font-weight: bold;">Forgot Password?</a>
                <br><br>
                <a href="register.php">Don't have an account? Sign up</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate Title Letters
            const title = document.querySelector('.header-title');
            const text = title.textContent.trim();
            title.innerHTML = '';
            [...text].forEach((char, index) => {
                const span = document.createElement('span');
                span.textContent = char === ' ' ? '\u00A0' : char;
                span.style.animationDelay = `${index * 0.1}s`;
                title.appendChild(span);
            });

            // Password visibility toggle
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');

            if (passwordInput && toggleBtn) {
                passwordInput.addEventListener('input', function() {
                    this.value.length > 0 ? toggleBtn.classList.add('show') : toggleBtn.classList.remove('show');
                });

                toggleBtn.addEventListener('click', function () {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }

            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');

            form.addEventListener('submit', function(e) {
                let isValid = true;
                clearErrors();

                // Email Validation
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailInput.value.trim())) {
                    showError(emailInput, 'Please enter a valid email address.');
                    isValid = false;
                }

                // Password Validation
                if (passwordInput.value.trim() === '') {
                    showError(passwordInput, 'Password is required.');
                    isValid = false;
                }

                if (!isValid) e.preventDefault();
            });

            function showError(input, message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-msg-js';
                errorDiv.innerText = message;
                input.style.borderColor = '#ff4757';
                input.parentNode.insertBefore(errorDiv, input.nextSibling);
            }

            function clearErrors() {
                document.querySelectorAll('.error-msg-js').forEach(el => el.remove());
                document.querySelectorAll('input').forEach(input => input.style.borderColor = 'rgba(255, 255, 255, 0.1)');
            }

            // Clear error on input
            [emailInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    const nextEl = this.nextElementSibling;
                    if (nextEl && nextEl.classList.contains('error-msg-js')) {
                        nextEl.remove();
                        this.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    }
                });
            });
        });
    </script>
</body>
</html>