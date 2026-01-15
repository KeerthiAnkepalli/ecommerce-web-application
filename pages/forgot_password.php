<?php

session_start();
include '../includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Load PHPMailer
if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
} else {
    require '../PHPMailer/src/Exception.php';
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';
}

$message = '';
$error = '';

if (isset($_POST['send_reset_link'])) {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        try {
            // Generate a unique, secure token
            $token = bin2hex(random_bytes(50));

            // Store token in the database using MySQL time to avoid timezone mismatches
            $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $updateStmt->execute([$token, $user['id']]);

            // Send email with PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'keerthiankepalli@gmail.com'; // Your admin email
                $mail->Password   = 'zydnddsozlefbnhg';           // Your Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('keerthiankepalli@gmail.com', 'Ecommerce Store Support');
                $mail->addAddress($email, $user['name']);

                // Content
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $reset_link = "$protocol://$host$path/reset_password.php?token=$token";
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "Hi {$user['name']},<br><br>You requested a password reset. Click the link below to reset your password:<br><a href='{$reset_link}'>{$reset_link}</a><br><br>This link will expire in 1 hour.<br><br>If you did not request this, please ignore this email.";
                $mail->AltBody = "Hi {$user['name']},\n\nYou requested a password reset. Copy and paste the following link into your browser to reset your password:\n{$reset_link}\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.";

                $mail->send();
            } catch (Exception $e) {
                // Don't expose detailed error to user, but log it
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }

        } catch (Exception $e) {
            error_log("Token Generation/DB Error: " . $e->getMessage());
        }
    }
    
    // Always show a generic message to prevent email enumeration attacks
    $message = 'If an account with that email exists, a password reset link has been sent.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reusing styles from login/register for consistency */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #020617; overflow: hidden; position: relative; }
        body::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(2, 6, 23, 0.85), rgba(15, 23, 42, 0.9)), url('../images/loginpage.jpg'); background-size: cover; background-position: center; z-index: -1; }
        .form-container { width: 100%; max-width: 450px; padding: 40px; box-sizing: border-box; border-radius: 24px; background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5); }
        h2 { text-align: center; color: #ffffff; margin-bottom: 20px; font-weight: 700; font-size: 2rem; }
        p.instructions { color: #94a3b8; text-align: center; font-size: 0.95rem; margin-bottom: 25px; }
        input[type="email"] { width: 100%; color: #fff; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 16px; border-radius: 16px; font-size: 1rem; margin-bottom: 16px; box-sizing: border-box; transition: all 0.3s; }
        input:focus { border-color: #00f2ff; box-shadow: 0 0 0 4px rgba(0, 242, 255, 0.15); outline: none; }
        button { width: 100%; padding: 12px; margin-top: 10px; background: linear-gradient(135deg, #00f2ff 0%, #0072ff 100%); border: none; color: white; font-size: 1.1rem; font-weight: 600; border-radius: 16px; cursor: pointer; transition: all 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(0, 114, 255, 0.6); }
        .message, .error { text-align: center; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: 500; }
        .message { background-color: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid #10b981; }
        .error { background-color: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #94a3b8; text-decoration: none; font-weight: 500; transition: color 0.3s; }
        .back-link a:hover { color: #00f2ff; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Forgot Your Password?</h2>
        <p class="instructions">Enter your email address and we will send you a link to reset your password.</p>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email address" value="<?= isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '' ?>" required>
            <button type="submit" name="send_reset_link">Send Reset Link</button>
        </form>
        <div class="back-link">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
