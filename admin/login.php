<?php
include '../includes/db.php';
session_start();

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id'] = $user['id'];
        header("Location: dashboard.php");
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
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            background-color: #1e293b;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), 0 0 30px rgba(0, 242, 255, 0.2);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 20px;
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 0 0 15px rgba(0, 242, 255, 0.5);
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #e0e0e0;
        }

        .login-container input[type="email"],
        .login-container input[type="password"],
        .login-container input[type="text"] {
            width: 100%;
            color: white;
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px;
            border-radius: 10px;
            font-size: 1rem;
            margin-bottom: 20px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            appearance: none;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }
        .password-wrapper input {
            margin-bottom: 0 !important;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.7);
        }

        .login-container input:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: #d946ef;
            box-shadow: 0 0 25px rgba(217, 70, 239, 0.5);
            transform: scale(1.02);
            outline: none;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(45deg, #00f2ff, #0072ff, #d946ef);
            background-size: 200% auto;
            border: none;
            color:white;
            font-size: 16px;
            font-weight: bold;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 242, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        button:hover {
            background-position: right center;
            box-shadow: 0 0 30px rgba(217, 70, 239, 0.6);
            transform: translateY(-2px);
        }
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }
        .form-footer a:hover {
            color: #00f2ff !important;
            text-shadow: 0 0 10px rgba(0, 242, 255, 0.8);
        }
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
    </style>
</head>
<body>
    <div id="errorPopup" style="display: none;"></div>


    


    <div class="login-container">
        <h2>Admin Login</h2>
        <form method="POST">
            <label for="email">Email</label>
            <input type="email"name="email" id="email" autocomplete="off" required>

            <label for="password">Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" required>
                <i class="fas fa-eye toggle-password"></i>
            </div>

            <button type="submit" name="login">Login</button>
        </form>
        <div class="form-footer">
            <a href="../forgot_password.php" style="color: #3498db; text-decoration: none; font-weight: bold;">Forgot Password?</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

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
                input.style.borderColor = '#ff6b6b';
                input.parentNode.insertBefore(errorDiv, input.nextSibling);
            }

            function clearErrors() {
                document.querySelectorAll('.error-msg-js').forEach(el => el.remove());
                document.querySelectorAll('input').forEach(input => input.style.borderColor = 'transparent');
            }

            // Clear error on input
            [emailInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    const nextEl = this.nextElementSibling;
                    if (nextEl && nextEl.classList.contains('error-msg-js')) {
                        nextEl.remove();
                        this.style.borderColor = 'transparent';
                    }
                });
            });

            // Password Toggle
            const toggleBtn = document.querySelector('.toggle-password');
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>