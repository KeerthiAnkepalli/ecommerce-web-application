<?php
ob_start();
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

// AJAX Handler for Quantity Update
if (isset($_POST['action']) && $_POST['action'] === 'update_cart_qty') {
    // Clear all output buffers to ensure clean JSON response
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        if ($stmt->execute([$quantity, $user_id, $product_id])) {
            // Recalculate totals
            $stmt = $conn->prepare("SELECT c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
            $stmt->execute([$user_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total_cost = 0;
            $total_items = 0;
            foreach ($items as $item) {
                $total_cost += $item['price'] * $item['quantity'];
                $total_items += $item['quantity'];
            }
            
            // Calculate Discount
            $discount = 0;
            if (isset($_SESSION['coupon'])) {
                if ($_SESSION['coupon']['type'] == 'percent') {
                    $discount = $total_cost * ($_SESSION['coupon']['value'] / 100);
                } else {
                    $discount = $_SESSION['coupon']['value'];
                }
            }
            $final_total = max(0, $total_cost - $discount);

            echo json_encode([
                'status' => 'success', 
                'subtotal' => number_format($total_cost, 2),
                'discount' => number_format($discount, 2),
                'total_cost' => number_format($final_total, 2), 
                'total_items' => $total_items
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
    }
    exit;
}

// AJAX Handler for Coupons
if (isset($_POST['action']) && $_POST['action'] === 'apply_coupon') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    $code = strtoupper(trim($_POST['code']));
    // Mock coupon logic - In a real app, check database
    if ($code === 'SAVE10') {
        $_SESSION['coupon'] = ['code' => 'SAVE10', 'type' => 'percent', 'value' => 10];
        echo json_encode(['status' => 'success', 'message' => '10% Discount Applied!']);
    } elseif ($code === 'FLAT50') {
        $_SESSION['coupon'] = ['code' => 'FLAT50', 'type' => 'fixed', 'value' => 50];
        echo json_encode(['status' => 'success', 'message' => '$50 Flat Discount Applied!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Coupon Code']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'remove_coupon') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    unset($_SESSION['coupon']);
    echo json_encode(['status' => 'success', 'message' => 'Coupon Removed']);
    exit;
}

$user_id = $_SESSION['user_id'];  // Assuming user ID is stored in session

// Handle Add to Cart with Quantity
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;  // Default to 1 if quantity is not set

    // Check if product is already in the user's cart
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        // Update quantity if the product is already in the cart
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$new_quantity, $user_id, $product_id]);
    } else {
        // Add new product to the cart
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }
}

// Handle Product Removal from Cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
}

// Handle Quantity Update
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Update the quantity in the cart
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $user_id, $product_id]);
}

// Handle Clear Cart
if (isset($_POST['clear_cart'])) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: cart.php");
    exit();
}

// Fetch the user's cart items
$stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = [];
if (!empty($cart_items)) {
    $product_ids = array_column($cart_items, 'product_id');
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total_cost = 0;  // Initialize total cost variable
$total_items = array_sum(array_column($cart_items, 'quantity'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #4f46e5;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-dark: #4338ca;
            --secondary: #64748b;
            --text-main: #1e293b;
            --text-light: #64748b;
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --accent: #f1f5f9;
            --danger: #ef4444;
            --success: #10b981;
            --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 16px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1557821552-17105176677c?auto=format&fit=crop&w=1920&q=80') no-repeat center center;
            background-size: cover;
            filter: blur(8px) brightness(0.9);
            z-index: -1;
            transform: scale(1.05);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Grid Layout for Desktop */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 30px;
            align-items: start;
        }

        .cart-items-container {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
            letter-spacing: -0.5px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 110px 1fr auto;
            gap: 24px;
            align-items: center;
            padding: 24px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--accent);
            border-radius: var(--radius);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .cart-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
            border-color: #d1d5db;
        }

        .cart-item img {
            width: 105px;
            height: 105px;
            object-fit: cover;
            border-radius: 12px;
            background: var(--accent);
            box-shadow: var(--shadow-soft);
            filter: contrast(1.05);
        }

        .item-details {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            line-height: 1.4;
        }

        .item-price {
            font-size: 1rem;
            color: var(--text-main);
            font-weight: 600;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            background: var(--accent);
            border-radius: 50px; /* Pill shape */
            padding: 4px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .btn-qty {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: var(--bg-card);
            color: var(--text-main);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .btn-qty:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .qty-val {
            min-width: 36px;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        .quantity-form {
            display: flex;
            align-items: center;
            background: var(--accent);
            padding: 5px;
            border-radius: 10px;
            border: 1px solid transparent;
        }

        .quantity {
            width: 50px;
            padding: 8px;
            border: none;
            background: transparent;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
            outline: none;
        }
        .quantity::-webkit-outer-spin-button,
        .quantity::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        
        .btn-update {
            background: transparent;
            color: #3b82f6;
            border: none;
            cursor: pointer;
            padding: 8px;
            transition: color 0.2s;
        }
        .btn-update:hover { color: #2563eb; }
        
        .btn-remove {
            background: transparent;
            border: none;
            color: var(--text-main);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-remove:hover {
            background: #fef2f2;
            color: var(--danger);
        }

        .cart-actions .btn-remove:hover {
            background: transparent;
        }
        
        /* Summary Section - Sidebar */
        .cart-summary-section {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1rem;
            color: var(--text-main);
            font-weight: 600;
        }

        .summary-row.total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #cbd5e1;
            font-weight: 700;
            color: var(--text-main);
            font-size: 1.4rem;
        }

        .coupon-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .coupon-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .coupon-input-group input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            transition: all 0.2s;
        }
        
        .coupon-input-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn-apply {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--text-main);
            padding: 0 5px;
            padding: 3px 10px;
            margin: 0;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: normal;
            white-space: nowrap;
            transition: all 0.2s;
        }
        
        .btn-apply:hover {
            background: var(--text-main);
            color: white;
        }

        .coupon-applied {
            background: #ecfdf5;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            border: 1px solid #a7f3d0;
        }

        .btn-remove-coupon {
            background: transparent;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
        }

        .cart-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }

        .btn-secondary {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            justify-content: center;
            gap: 8px;
            transition: color 0.2s;
            font-size: 0.95rem;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            color: var(--primary);
        }
        
        .btn-checkout {
            background: var(--primary-gradient);
            color: #ffffff;
            padding: 10px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .btn-checkout::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.5s;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.6);
        }

        .btn-checkout:hover::after {
            transform: translateX(100%);
        }
        
        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 30px;
            color: var(--text-main);
            font-size: 0.85rem;
            opacity: 1;
            font-weight: 600;
        }

        .trust-badges span { display: flex; align-items: center; gap: 8px; }
        .trust-badges i { color: var(--success); }

        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: var(--radius);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
            color: var(--text-light);
        }

        .empty-cart i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #cbd5e1;
            display: block;
        }
        
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 20px;
                margin: 20px auto;
            }
            .cart-layout {
                grid-template-columns: 1fr;
            }
            .cart-item { 
                grid-template-columns: 80px 1fr;
                gap: 16px;
            }
            .cart-item img {
                width: 80px;
                height: 80px;
                grid-row: span 2;
            }
            .item-actions { 
                grid-column: 1 / -1;
                flex-direction: row; 
                justify-content: space-between;
                align-items: center;
                margin-top: 10px;
                padding-top: 15px;
                border-top: 1px dashed var(--accent);
                width: 100%;
            }
            .cart-actions {
                flex-direction: column-reverse;
                gap: 15px;
            }
            .btn-checkout {
                width: 100%;
                justify-content: center;
            }
            .trust-badges {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        if (empty($cart_items)) {
            echo "<div class='empty-cart'>
                    <i class='fas fa-shopping-cart'></i>
                    <p>Your cart is currently empty.</p>
                    <a href='../index.php' class='btn-checkout' style='display:inline-block; margin-top:20px; background:#10b981;'>Start Shopping</a>
                  </div>";
        } else {
            echo '<div class="cart-layout">';
            
            // Left Column: Cart Items
            echo '<div class="cart-items-container">';
            echo '<h2>Shopping Cart ('. $total_items .' items)</h2>';
            
            foreach ($products as $product) {
                $quantity = 0;
                foreach ($cart_items as $cart_item) {
                    if ($cart_item['product_id'] == $product['id']) {
                        $quantity = $cart_item['quantity'];
                        break;
                    }
                }
                $total_cost += $product['price'] * $quantity; // Add product price * quantity to total cost

                echo "<div class='cart-item'>
                        <img src='../images/{$product['image']}' alt='{$product['name']}'>
                        <div class='item-details'>
                            <div class='item-name'>{$product['name']}</div>
                            <div class='item-price'>$" . number_format($product['price'], 2) . " <span id='price-qty-{$product['id']}' style='color:#9ca3af; font-size:0.9em;'>x $quantity</span></div>
                        </div>
                        <div class='item-actions'>
                            <div class='qty-controls'>
                                <button type='button' class='btn-qty' onclick='changeQty({$product['id']}, -1)'>-</button>
                                <span id='qty-val-{$product['id']}' class='qty-val'>$quantity</span>
                                <button type='button' class='btn-qty' onclick='changeQty({$product['id']}, 1)'>+</button>
                            </div>
                            <form method='POST'>
                                <input type='hidden' name='product_id' value='{$product['id']}'>
                                <button type='submit' name='remove_from_cart' class='btn-remove'><i class='fas fa-trash-alt'></i> Remove</button>
                            </form>
                        </div>
                      </div>";
            }
            echo '</div>'; // End cart-items-container

            // Right Column: Summary
            $discount = 0;
            if (isset($_SESSION['coupon'])) {
                if ($_SESSION['coupon']['type'] == 'percent') {
                    $discount = $total_cost * ($_SESSION['coupon']['value'] / 100);
                } else {
                    $discount = $_SESSION['coupon']['value'];
                }
            }
            $final_total = max(0, $total_cost - $discount);

            echo '<div class="cart-summary-section">
                    <div class="coupon-section">
                        ' . (isset($_SESSION['coupon']) ? 
                        '<div class="coupon-applied">
                            <span><i class="fas fa-tag"></i> ' . htmlspecialchars($_SESSION['coupon']['code']) . ' applied</span>
                            <button onclick="removeCoupon()" class="btn-remove-coupon"><i class="fas fa-times"></i></button>
                        </div>' : 
                        '<div class="coupon-input-group">
                            <input type="text" id="coupon_code" placeholder="Coupon Code">
                            <button onclick="applyCoupon()" class="btn-apply">Redeem</button>
                        </div>') . '
                    </div>
                    
                    <div class="summary-row"><span>Subtotal</span> <span id="summary-subtotal">$' . number_format($total_cost, 2) . '</span></div>
                    <div class="summary-row" style="color:var(--success);"><span>Discount</span> <span id="summary-discount">-$' . number_format($discount, 2) . '</span></div>
                    <div class="summary-row total"><span>Total</span> <span id="summary-total">$' . number_format($final_total, 2) . '</span></div>
                    
                    <div class="cart-actions">
                        <a href="checkout.php" class="btn-checkout">Proceed to Checkout <i class="fas fa-arrow-right"></i></a>
                        <form method="POST" style="margin: 0; width: 100%;">
                            <button type="submit" name="clear_cart" class="btn-remove" onclick="return confirm(\'Are you sure you want to clear your cart?\');" style="color: var(--text-main); width: 100%; justify-content: center; font-weight: 600;">
                                Clear Cart
                            </button>
                        </form>
                        <a href="../index.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                    </div>
                    <div class="trust-badges">
                        <span><i class="fas fa-lock"></i> Secure</span>
                        <span><i class="fas fa-shield-alt"></i> Protected</span>
                    </div>
                  </div>';
            
            echo '</div>'; // End cart-layout
        }
        ?>
    </div>
    <script>
        function changeQty(productId, delta) {
            const qtySpan = document.getElementById('qty-val-' + productId);
            let currentQty = parseInt(qtySpan.innerText);
            let newQty = currentQty + delta;
            if (newQty < 1) return;
            
            // Optimistic update
            qtySpan.innerText = newQty;
            document.getElementById('price-qty-' + productId).innerText = 'x ' + newQty;
            updateCartQty(productId, newQty);
        }

        function updateCartQty(productId, quantity) {
            const formData = new FormData();
            formData.append('action', 'update_cart_qty');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.status === 'success') {
                        // Update Totals
                        document.getElementById('summary-subtotal').innerText = '$' + data.subtotal;
                        document.getElementById('summary-discount').innerText = '-$' + data.discount;
                        document.getElementById('summary-total').innerText = '$' + data.total_cost;
                        document.querySelector('h2').innerText = 'Your Cart (' + data.total_items + ' items)';
                    } else {
                        console.error('Update failed:', data);
                        alert('Failed to update quantity: ' + (data.message || 'Unknown error'));
                    }
                } catch (e) {
                    console.error('Invalid JSON:', text);
                    alert('Server error. Please check console.');
                }
            });
        }

        function applyCoupon() {
            const code = document.getElementById('coupon_code').value;
            if(!code) return;
            
            const formData = new FormData();
            formData.append('action', 'apply_coupon');
            formData.append('code', code);

            fetch('cart.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }

        function removeCoupon() {
            const formData = new FormData();
            formData.append('action', 'remove_coupon');

            fetch('cart.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                location.reload();
            });
        }
    </script>
</body>
</html>