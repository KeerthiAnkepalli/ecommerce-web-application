<?php
session_start();
include '../includes/db.php';

if (isset($_GET['msg']) && $_GET['msg'] == 'not_found') {
    $error = "Email not found. Please create an account.";
}

if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile= $_POST['mobile'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Basic check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    
    // Check if mobile number exists
    $checkMobile = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
    $checkMobile->execute([$mobile]);
    
    if ($check->rowCount() > 0) {
        $error = "Email already registered.";
    } elseif ($checkMobile->rowCount() > 0) {
        $error = "Mobile number already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, password, role) VALUES (?, ?, ?, ?, 'customer')");
        if ($stmt->execute([$name, $email, $mobile, $password])) {
            $_SESSION['user_id'] = $conn->lastInsertId();
            header("Location: ../index.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #020617;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(2, 6, 23, 0.85), rgba(15, 23, 42, 0.9)), url('../images/loginpage.jpg');
            background-size: cover;
            background-position: center;
            z-index: -1;
            animation: pulseBackground 10s ease-in-out infinite alternate;
        }

        /* Floating Particles */
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

        .register-card {
            width: 100%;
            max-width: 450px;
            padding: 40px;
            box-sizing: border-box;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            border-left: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            animation: floatCard 6s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes floatCard {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h2 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 2rem;
            background: linear-gradient(to right, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        #password-strength-meter {
            height: 4px;
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            margin-top: 8px;
            margin-bottom: 8px;
            overflow: hidden;
        }
        .strength-bar-fill {
            height: 100%;
            width: 0;
            background: #ef4444;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 2px;
        }
        #password-strength-text {
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: right;
            margin-top: 0;
            margin-bottom: 16px;
            height: 1.2em;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 16px;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.3s;
        }
        .toggle-password:hover {
            color: #fff;
        }

        input {
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
        }
        .input-wrapper input {
            margin-bottom: 0;
        }

        input:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: #00f2ff;
            box-shadow: 0 0 0 4px rgba(0, 242, 255, 0.15);
            outline: none;
            transform: translateY(-1px);
        }
        
        input::placeholder {
            color: #64748b;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(0, 114, 255, 0.6);
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .links a:hover {
            color: #00f2ff;
        }

        .error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 10px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        @media (max-width: 576px) {
            .register-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <h2>Create Account</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="mobile" placeholder="Mobile Number" required>
            <div class="input-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <div id="password-strength-meter">
                <div class="strength-bar-fill"></div>
            </div>
            <p id="password-strength-text"></p>
            <button type="submit" name="register">Sign Up</button>
        </form>
        <div class="links">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-password').forEach(item => {
                item.addEventListener('click', function () {
                    const passwordInput = this.previousElementSibling;
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            });

            const passwordInput = document.getElementById('password');
            const strengthMeterFill = document.querySelector('.strength-bar-fill');
            const strengthText = document.getElementById('password-strength-text');

            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);
                
                let strengthValue = 0;
                let text = '';
                let color = '#ef4444'; // weak

                if (strength.length) strengthValue += 25;
                if (strength.hasLowerCase) strengthValue += 15;
                if (strength.hasUpperCase) strengthValue += 15;
                if (strength.hasNumber) strengthValue += 20;
                if (strength.hasSpecialChar) strengthValue += 25;

                if (password.length === 0) {
                    strengthValue = 0;
                }

                strengthMeterFill.style.width = strengthValue + '%';

                if (strengthValue < 50) {
                    text = 'Weak';
                    color = '#ef4444';
                } else if (strengthValue < 80) {
                    text = 'Medium';
                    color = '#f59e0b';
                } else {
                    text = 'Strong';
                    color = '#10b981';
                }
                
                strengthMeterFill.style.backgroundColor = color;
                
                strengthText.textContent = password.length > 0 ? text : '';
            });

            function checkPasswordStrength(password) {
                return {
                    length: password.length >= 8, hasUpperCase: /[A-Z]/.test(password),
                    hasLowerCase: /[a-z]/.test(password), hasNumber: /[0-9]/.test(password),
                    hasSpecialChar: /[^A-Za-z0-9]/.test(password)
                };
            });
        });
    </script>
</body>
</html>