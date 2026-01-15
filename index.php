<?php
session_start();
include 'includes/db.php';


// Fetch products from database
try {
    $stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $products = [];
}

// Handle Add to Cart
$message = "";
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: pages/login.php");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $quantity = 1;

    $check = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $check->execute([$user_id, $product_id]);
    
    if ($check->rowCount() > 0) {
        $update = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
        $update->execute([$user_id, $product_id]);
    } else {
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert->execute([$user_id, $product_id, $quantity]);
    }
    $message = "Item added to cart!";
}

// Handle Newsletter Subscription
if (isset($_POST['subscribe'])) {
    $email = trim($_POST['email']);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
            $stmt->execute([$email]);

            // Send Welcome Email
            $to = $email;
            $subject = "Welcome to Store.";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Store <no-reply@example.com>" . "\r\n";

            $emailBody = "
            <h2 style='color: #333;'>Welcome to Store.</h2>
            <p style='color: #555;'>Thank you for subscribing! You'll now be the first to know about our new arrivals and exclusive deals.</p>
            <p style='color: #555;'>Happy Shopping!</p>";

            sendMail($to, $subject, $emailBody);

            $message = "Subscribed successfully! Welcome email sent.";
        } catch (PDOException $e) {
            $message = "You are already subscribed.";
        }
    } else {
        $message = "Invalid email address.";
    }
}

// Handle Contact Form
if (isset($_POST['contact_submit'])) {
    $c_name = htmlspecialchars($_POST['contact_name']);
    $c_email = filter_var($_POST['contact_email'], FILTER_SANITIZE_EMAIL);
    $c_msg = htmlspecialchars($_POST['contact_message']);

    if ($c_email && !empty($c_msg)) {
        // Save to Database
        try {
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
            $stmt->execute([$c_name, $c_email, $c_msg]);
        } catch (Exception $e) {
            // Silent fail if table doesn't exist yet
        }

        $to = "admin@example.com"; // Replace with your actual admin email
        $subject = "New Contact Message from $c_name";
        $headers = "From: $c_email";
        sendMail($to, $subject, "<strong>From:</strong> $c_name ($c_email)<br><br><strong>Message:</strong><br>" . nl2br($c_msg));
        $message = "Message sent successfully!";
    } else {
        $message = "Please fill in all fields.";
    }
}

// Fetch Cart Count & Preview
$cart_count = 0;
$cart_preview = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetchColumn() ?: 0;

    if ($cart_count > 0) {
        $stmt = $conn->prepare("SELECT p.name, p.image, p.price, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ORDER BY c.id DESC LIMIT 3");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_preview = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');

        :root {
            --primary: #00f2ff;
            --secondary: #d946ef;
            --bg-dark: #020617;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-hover: rgba(255, 255, 255, 0.1);
            --border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-dark);
            color: #fff;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                linear-gradient(rgba(2, 6, 23, 0.85), rgba(2, 6, 23, 0.95)),
                url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            z-index: -2;
            animation: pulseBG 10s ease-in-out infinite alternate;
        }

        @keyframes pulseBG {
            0% { opacity: 0.6; transform: scale(1); }
            100% { opacity: 1; transform: scale(1.1); }
        }

        /* Navbar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            background: rgba(2, 6, 23, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(to right, #fff, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-links a {
            color: #cbd5e1;
            text-decoration: none;
            margin-left: 30px;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary);
            text-shadow: 0 0 10px rgba(0, 242, 255, 0.5);
        }

        .cart-badge {
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
            position: absolute;
            top: -8px;
            right: -12px;
            font-weight: 700;
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.4);
        }

        /* Cart Dropdown */
        .nav-item { position: relative; display: flex; align-items: center; }
        .cart-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            width: 320px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(15px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            z-index: 1001;
            animation: fadeIn 0.3s ease;
        }
        .nav-item:hover .cart-dropdown { display: block; }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .item-info { flex: 1; overflow: hidden; }
        .item-name { display: block; font-size: 0.9rem; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .item-price { font-size: 0.85rem; color: #94a3b8; }
        .view-cart-btn {
            display: block;
            text-align: center;
            background: linear-gradient(135deg, var(--primary), #0072ff);
            color: #fff !important;
            padding: 12px;
            border-radius: 10px;
            margin-top: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            margin-left: 0 !important;
            text-shadow: none !important;
        }
        .view-cart-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 114, 255, 0.3); }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 80px 20px;
            animation: fadeIn 1s ease-out;
        }

        .hero h1 {
            font-size: 4rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.2rem;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto 40px;
        }

        /* Product Grid */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            padding-bottom: 50px;
        }

        .product-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            opacity: 0;
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 
                        0 0 20px rgba(0, 242, 255, 0.1);
            border-color: rgba(0, 242, 255, 0.3);
        }

        .img-container {
            width: 100%;
            height: 180px;
            overflow: hidden;
            position: relative;
        }

        .product-card img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 15px;
            box-sizing: border-box;
            transition: transform 0.6s ease;
        }

        .product-card:hover img {
            transform: scale(1.1);
        }

        .product-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 10px;
            color: #fff;
        }

        .product-desc {
            font-size: 0.9rem;
            color: #94a3b8;
            margin-bottom: 15px;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(0, 242, 255, 0.3);
        }

        .btn-add {
            background: linear-gradient(135deg, var(--primary), #0072ff);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 114, 255, 0.4);
        }

        /* Notification */
        .alert {
            position: fixed;
            top: 100px;
            right: 20px;
            background: rgba(16, 185, 129, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: slideIn 0.5s ease-out, fadeOut 0.5s ease-in 2.5s forwards;
            z-index: 2000;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeOut {
            to { opacity: 0; visibility: hidden; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); }
            nav { padding: 15px 20px; }
            .cart-dropdown { display: none !important; } /* Hide hover on mobile */
        }
    </style>
    <style>
        /* Contact Section */
        .contact-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            background: var(--glass);
            padding: 50px;
            border-radius: 24px;
            border: 1px solid var(--border);
            backdrop-filter: blur(10px);
            margin-top: 80px;
        }
        .contact-info h2 { font-size: 2.5rem; margin-bottom: 20px; color: #fff; }
        .contact-info p { color: #94a3b8; margin-bottom: 30px; line-height: 1.6; }
        .info-item { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; color: #cbd5e1; }
        .info-item i { color: var(--primary); font-size: 1.2rem; width: 20px; }
        
        .contact-form .form-group { margin-bottom: 20px; }
        .contact-form input, .contact-form textarea {
            width: 100%; padding: 15px; background: rgba(255,255,255,0.05);
            border: 1px solid var(--border); border-radius: 12px; color: #fff;
            font-family: inherit; outline: none; transition: 0.3s; box-sizing: border-box;
        }
        .contact-form input:focus, .contact-form textarea:focus { border-color: var(--primary); background: rgba(255,255,255,0.1); }
        @media (max-width: 768px) { .contact-wrapper { grid-template-columns: 1fr; padding: 30px; } }
    </style>
    <style>
        /* Footer */
        footer {
            background: rgba(2, 6, 23, 0.95);
            border-top: 1px solid var(--border);
            padding: 60px 20px 20px;
            margin-top: 50px;
            backdrop-filter: blur(20px);
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-section h3 { color: #fff; margin-bottom: 20px; }
        .footer-section p { color: #94a3b8; line-height: 1.6; }
        .newsletter-form { display: flex; gap: 10px; margin-top: 15px; }
        .newsletter-form input {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            color: #fff;
            outline: none;
        }
        .newsletter-form button {
            padding: 12px 20px;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .newsletter-form button:hover { background: #fff; }
        .footer-bottom { text-align: center; color: #64748b; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav>
        <a href="index.php" class="logo">Store<span style="color:var(--primary)">.</span></a>
        <div class="nav-links" style="display: flex; align-items: center;">
            <a href="index.php">Home</a>
            <div class="nav-item">
                <a href="pages/cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?= $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <div class="cart-dropdown">
                    <?php if (!empty($cart_preview)): ?>
                        <?php foreach ($cart_preview as $item): ?>
                            <div class="dropdown-item">
                                <img src="images/<?= htmlspecialchars($item['image']); ?>" alt="Product">
                                <div class="item-info">
                                    <span class="item-name"><?= htmlspecialchars($item['name']); ?></span>
                                    <span class="item-price"><?= $item['quantity']; ?> x $<?= number_format($item['price'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="pages/cart.php" class="view-cart-btn">View Full Cart</a>
                    <?php else: ?>
                        <div style="text-align: center; color: #94a3b8; padding: 15px;">Your cart is empty</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="pages/orders.php">Orders</a>
                <a href="pages/logout.php" style="color: #ef4444;">Logout</a>
            <?php else: ?>
                <a href="pages/login.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Notification -->
    <?php if($message): ?>
        <div class="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="hero">
        <h1>Future of Shopping</h1>
        <p>Discover our premium collection with an immersive experience.</p>
    </div>

    <!-- Products -->
    <div class="container">
        <div class="products-grid">
            <?php foreach ($products as $index => $product): ?>
                <div class="product-card" style="animation-delay: <?= $index * 0.1; ?>s;">
                    <div class="img-container">
                        <img src="images/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($product['name']); ?></div>
                        <div class="product-desc"><?= htmlspecialchars($product['description']); ?></div>
                        <div class="price-row">
                            <div class="price">$<?= number_format($product['price'], 2); ?></div>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                <button type="submit" name="add_to_cart" class="btn-add">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Contact Section -->
    <div class="container">
        <div class="contact-wrapper">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p>Have questions about our products or your order? Send us a message and we'll respond as soon as possible.</p>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>123 Tech Street, Silicon Valley, CA</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span>support@store.com</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <span>+1 (555) 123-4567</span>
                </div>
            </div>
            <form method="POST" class="contact-form">
                <div class="form-group"><input type="text" name="contact_name" placeholder="Your Name" required></div>
                <div class="form-group"><input type="email" name="contact_email" placeholder="Your Email" required></div>
                <div class="form-group"><textarea name="contact_message" rows="5" placeholder="Your Message" required></textarea></div>
                <button type="submit" name="contact_submit" class="btn-add" style="width: 100%; justify-content: center;">Send Message</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Store<span style="color:var(--primary)">.</span></h3>
                <p>Experience the future of shopping with our premium collection of tech and lifestyle products.</p>
            </div>
            <div class="footer-section">
                <h3>Newsletter</h3>
                <p>Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.</p>
                <form method="POST" class="newsletter-form">
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <button type="submit" name="subscribe">Join</button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y'); ?> E-Commerce Store. All rights reserved.
        </div>
    </footer>

</body>
</html>