<?php
session_start();
include '../includes/db.php';

$message = '';
$error = '';
$show_form = false;
$token = '';
$show_resend = false;
$success = false;
$email = '';

try {
    // 1. Get token from POST or GET
    $token = $_POST['token'] ?? $_GET['token'] ?? '';

    if (empty($token)) {
        $error = "No token provided.";
    } else {
        // 2. Verify token and check if it is not expired
        // Corrected column name from reset_token_expiry to reset_expires
        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $show_form = true;

            // Handle Form Submission
            if (isset($_POST['reset_password'])) {
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];

                if ($password === $confirm_password) {
                    if (strlen($password) >= 6) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // 3. Update password and clear token
                        // Corrected column name from reset_token_expiry to reset_expires
                        $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                        if ($updateStmt->execute([$hashed_password, $user['id']])) {
                            $success = true;
                            $show_form = false;
                        } else {
                            $error = "Failed to reset password. Please try again.";
                            $show_form = true;
                        }
                    } else {
                        $error = "Password must be at least 6 characters long.";
                        $show_form = true;
                    }
                } else {
                    $error = "Passwords do not match.";
                    $show_form = true;
                }
            }
        } else {
            // Check if token exists but is expired
            $stmtExpired = $conn->prepare("SELECT email FROM users WHERE reset_token = ?");
            $stmtExpired->execute([$token]);
            if ($row = $stmtExpired->fetch(PDO::FETCH_ASSOC)) {
                $email = $row['email'];
                $error = "This password reset link has expired.";
                $show_resend = true;
            } else {
                $error = "This password reset link is invalid.";
            }
        }
    }
} catch (Exception $e) {
    $error = "An error occurred while processing your request. Please try again.";
    // error_log($e->getMessage()); // Log the actual error for debugging
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #020617; overflow: hidden; position: relative; }
        body::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(2, 6, 23, 0.85), rgba(15, 23, 42, 0.9)), url('../images/loginpage.jpg'); background-size: cover; background-position: center; z-index: -1; }
        .form-container { width: 100%; max-width: 450px; padding: 40px; box-sizing: border-box; border-radius: 24px; background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5); }
        h2 { text-align: center; color: #ffffff; margin-bottom: 20px; font-weight: 700; font-size: 2rem; }
        input { width: 100%; color: #fff; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 16px; border-radius: 16px; font-size: 1rem; margin-bottom: 16px; box-sizing: border-box; transition: all 0.3s; }
        input:focus { border-color: #00f2ff; box-shadow: 0 0 0 4px rgba(0, 242, 255, 0.15); outline: none; }
        button { width: 100%; padding: 12px; margin-top: 10px; background: linear-gradient(135deg, #00f2ff 0%, #0072ff 100%); border: none; color: white; font-size: 1.1rem; font-weight: 600; border-radius: 16px; cursor: pointer; transition: all 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(0, 114, 255, 0.6); }
        .message { background-color: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid #10b981; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; }
        .error { background-color: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #94a3b8; text-decoration: none; font-weight: 500; transition: color 0.3s; }
        .back-link a:hover { color: #00f2ff; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 1000; backdrop-filter: blur(5px); }
        .modal-content { background: #1e293b; padding: 40px; border-radius: 24px; text-align: center; color: #fff; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); max-width: 90%; width: 400px; }
        .success-icon { width: 60px; height: 60px; background: rgba(16, 185, 129, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .btn-login { display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-decoration: none; padding: 12px 30px; border-radius: 12px; font-weight: 600; margin-top: 20px; transition: transform 0.2s; }
        .btn-login:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <?php if ($success): ?>
    <div class="modal">
        <div class="modal-content">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h3 style="margin: 0 0 10px 0; font-size: 1.5rem;">Success!</h3>
            <p style="color: #94a3b8; margin: 0;">Your password has been changed successfully.</p>
            <a href="login.php" class="btn-login">Back to Login</a>
            <script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000);
            </script>
        </div>
    </div>
    <?php endif; ?>

    <div class="form-container">
        <h2>Reset Password</h2>
        <?php if ($message): ?> <div class="message"><?= $message ?></div> <?php endif; ?>
        <?php if ($error): ?> 
            <div class="error"><?= htmlspecialchars($error) ?></div> 
            <?php if ($show_resend): ?>
                <div style="text-align: center; margin-bottom: 20px;">
                    <a href="forgot_password.php?email=<?= urlencode($email) ?>" style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #00f2ff 0%, #0072ff 100%); color: white; text-decoration: none; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 15px rgba(0, 114, 255, 0.3); transition: transform 0.2s;">Resend Link</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($show_form): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="input-wrapper">
                <input type="password" name="password" placeholder="New Password" required>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <div class="input-wrapper">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <button type="submit" name="reset_password">Reset Password</button>
        </form>
        <?php endif; ?>
        
        <div class="back-link"><a href="login.php">Back to Login</a></div>
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
        });
    </script>
</body>
</html>