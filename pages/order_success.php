<?php
session_start();
if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}
$order_id = htmlspecialchars($_GET['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            position: relative;
            color: #e2e8f0;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.85) 0%, rgba(30, 41, 59, 0.9) 100%), url('https://images.unsplash.com/photo-1563013544-824ae1b704d3?auto=format&fit=crop&w=1920&q=80') no-repeat center center;
            background-size: cover;
            filter: blur(5px);
            transform: scale(1.05);
            z-index: -1;
        }
        .success-card {
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            padding: 50px;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 500px;
            width: 90%;
        }
        .icon-circle {
            width: 70px;
            height: 70px;
            background: rgba(16, 185, 129, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        .icon-circle i { font-size: 32px; color: #10b981; }
        h1 { color: #ffffff; margin-bottom: 10px; font-size: 2rem; }
        p { color: #cbd5e1; margin-bottom: 30px; line-height: 1.6; }
        .order-id { font-weight: 700; color: #e2e8f0; background: rgba(255, 255, 255, 0.1); padding: 5px 12px; border-radius: 8px; }
        .btn-home {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        .btn-home:hover { 
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.5);
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="icon-circle"><i class="fas fa-check"></i></div>
        <h1>Order Placed!</h1>
        <p>Thank you for your purchase. Your order <span class="order-id">#<?= $order_id; ?></span> has been confirmed. We have sent a confirmation email with the details.</p>
        <a href="../index.php" class="btn-home">Continue Shopping</a>
    </div>
</body>
</html>
