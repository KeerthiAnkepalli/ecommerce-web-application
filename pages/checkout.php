<?php
session_start();
include '../includes/db.php';
include 'mailer.php';

if (isset($_POST['action']) && $_POST['action'] === 'delete_address') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
        exit;
    }
    if (!isset($_POST['address_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Address ID missing.']);
        exit;
    }

    $address_id = $_POST['address_id'];
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$address_id, $user_id])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Address not found or permission denied.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to execute delete statement.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
    }
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Cart Items
$stmt = $conn->prepare("SELECT c.quantity, p.id, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// 2. Calculate Totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$discount = 0;
if (isset($_SESSION['coupon'])) {
    if ($_SESSION['coupon']['type'] == 'percent') {
        $discount = $subtotal * ($_SESSION['coupon']['value'] / 100);
    } else {
        $discount = $_SESSION['coupon']['value'];
    }
}

$shipping = ($subtotal > 100) ? 0 : 10.00; // Example: Free shipping over $100
$total = max(0, $subtotal + $shipping - $discount);

// Fetch Saved Addresses from the new user_addresses table
$stmt_addr = $conn->prepare("SELECT id, full_name, phone, address_line1, address_line2, is_default FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt_addr->execute([$user_id]);
$saved_addresses = $stmt_addr->fetchAll(PDO::FETCH_ASSOC);

// 3. Handle Order Placement
if (isset($_POST['place_order'])) {
    $name = htmlspecialchars(trim($_POST['full_name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $address = htmlspecialchars(trim($_POST['address']));
    $payment = htmlspecialchars(trim($_POST['payment_method']));
    $set_default = isset($_POST['set_default']) ? 1 : 0;

    if ($name && $phone && $address && $payment) {
        try {
            $conn->beginTransaction();

            // Insert Order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, full_name, phone, address, payment_method, subtotal, shipping, discount, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $phone, $address, $payment, $subtotal, $shipping, $discount, $total]);
            $order_id = $conn->lastInsertId();

            // Insert Order Items
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $stmt_item->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
            }

            // Address Management
            $stmt_check_addr = $conn->prepare("SELECT id FROM user_addresses WHERE user_id = ? AND full_name = ? AND phone = ? AND address_line1 = ?");
            $stmt_check_addr->execute([$user_id, $name, $phone, $address]);
            $existing_addr = $stmt_check_addr->fetch(PDO::FETCH_ASSOC);

            if ($set_default) {
                $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
            }

            if ($existing_addr) {
                if ($set_default) {
                    $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ?")->execute([$existing_addr['id']]);
                }
            } else {
                $stmt_save_addr = $conn->prepare("INSERT INTO user_addresses (user_id, full_name, phone, address_line1, address_line2, is_default) VALUES (?, ?, ?, ?, '', ?)");
                $stmt_save_addr->execute([$user_id, $name, $phone, $address, $set_default]);
            }

            // Clear Cart
            $stmt_clear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt_clear->execute([$user_id]);
            unset($_SESSION['coupon']);

            $conn->commit();

            // Send Confirmation Email
            $user_email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
            $user_email_stmt->execute([$user_id]);
            $user_email = $user_email_stmt->fetchColumn();

            if ($user_email) {
                $subject = "Order Confirmation #$order_id";
                $body = "<h2>Thank you for your order!</h2><p>Your order #$order_id has been placed successfully.</p><p>Total: $" . number_format($total, 2) . "</p>";
                sendMail($user_email, $subject, $body);
            }

            header("Location: order_success.php?id=$order_id");
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Failed to place order. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            /* background-color: #f8fafc; Removed for background image */
            color: #e2e8f0;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            position: relative;
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

        .checkout-header {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
            align-items: start;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #fff;
            letter-spacing: -0.02em;
        }

        .card {
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Form Styles */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.9rem; color: #cbd5e1; }
        input, textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
            transition: all 0.2s ease;
            color: #fff;
        }
        input:focus, textarea:focus { outline: none; border-color: #4f46e5; background: rgba(0, 0, 0, 0.4); box-shadow: 0 0 15px rgba(79, 70, 229, 0.3); transform: scale(1.01); }
        input::placeholder, textarea::placeholder { color: #64748b; }

        /* Payment Options */
        .payment-options { display: flex; flex-direction: column; gap: 12px; }
        .payment-option {
            display: flex;
            align-items: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .payment-option:hover { border-color: rgba(255, 255, 255, 0.3); background: rgba(0, 0, 0, 0.3); }
        .payment-option input { width: auto; margin-right: 15px; }
        .payment-label { font-weight: 500; flex: 1; color: #fff; }
        .payment-icons { color: #94a3b8; font-size: 1.2rem; }

        /* Order Summary */
        .summary-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .summary-item img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
        }
        .item-info h4 { margin: 0 0 5px; font-size: 0.95rem; color: #fff; }
        .item-meta { font-size: 0.85rem; color: #94a3b8; }
        .item-price { margin-left: auto; font-weight: 600; color: #fff; }

        .totals { margin-top: 20px; }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: #cbd5e1;
            font-size: 0.95rem;
        }
        .total-row.final {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed rgba(255, 255, 255, 0.2);
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            align-items: center;
        }
        .total-row.final span:last-child { color: #10b981; }

        /* Button */
        .btn-place-order {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        .btn-place-order:hover { 
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.5);
        }
        .btn-place-order:active { transform: translateY(0); }

        .btn-place-order:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Reassurance */
        .reassurance {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .reassurance span { display: flex; align-items: center; gap: 6px; }
        .reassurance i { color: #10b981; }

        .address-option {
            position: relative;
        }

        .address-actions {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            display: flex;
            gap: 5px;
            opacity: 0; /* Hidden by default */
            transition: opacity 0.2s;
        }
        .address-option:hover .address-actions { opacity: 1; }

        .btn-action {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-action:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }
        .btn-action.delete:hover { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .btn-action.edit:hover { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }

        /* Saved Addresses */
        .saved-addresses { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .address-option { display: flex; flex-direction: column; gap: 10px; padding: 15px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; cursor: pointer; transition: all 0.25s ease; position: relative; background: rgba(0, 0, 0, 0.2); }
        .address-option:hover { border-color: rgba(255, 255, 255, 0.3); transform: translateY(-5px); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2); background: rgba(0, 0, 0, 0.3); }
        .address-option.selected { border-color: #4f46e5; background: rgba(79, 70, 229, 0.1); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2); border: 1px solid #4f46e5; }
        .address-option input[type="radio"] { position: absolute; top: 15px; right: 15px; width: 1.2em; height: 1.2em; accent-color: #4f46e5; }
        .address-details { font-size: 0.9rem; color: #cbd5e1; line-height: 1.4; }
        
        /* Add New Address Card */
        .address-option.add-new { border: 2px dashed rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.05); justify-content: center; align-items: center; }
        .address-option.add-new:hover { border-color: #4f46e5; background: rgba(255, 255, 255, 0.1); }
        .address-option.add-new .address-details { text-align: center; }

        .order-summary { position: sticky; top: 20px; }

        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
            .order-summary { grid-row: 2; position: static; } /* Summary bottom on mobile */
            .checkout-form { grid-row: 1; }
        }
    </style>
</head>
<body>

    <header class="checkout-header">
        <a href="../index.php" class="logo">Store<span style="color:#4f46e5">.</span> Checkout</a>
    </header>

    <div class="container">
        <!-- Left Column: Form -->
        <div class="checkout-form">
            <form method="POST">
                <?php if (!empty($saved_addresses)): ?>
                <div class="card" style="margin-bottom: 25px;">
                    <h3 class="section-title">Saved Addresses</h3>
                    <div class="saved-addresses">
                        <?php foreach ($saved_addresses as $idx => $addr): ?>
                    <?php 
                        $display_address = $addr['address_line1'] . ($addr['address_line2'] ? ', ' . $addr['address_line2'] : '');
                    ?>
                        <label class="address-option <?= $idx === 0 ? 'selected' : '' ?>">
                            <input type="radio" name="address_selection" 
                                data-name="<?= htmlspecialchars($addr['full_name']); ?>"
                                data-phone="<?= htmlspecialchars($addr['phone']); ?>"
                            data-address="<?= htmlspecialchars($display_address); ?>"
                                <?= $idx === 0 ? 'checked' : '' ?>>
                            <div class="address-details">
                                <strong><?= htmlspecialchars($addr['full_name']); ?></strong><br>
                                <?php if ($addr['is_default']): ?> <span style="background:#dcfce7; color:#166534; padding:2px 6px; border-radius:4px; font-size:0.75rem; margin-left:5px;">Default</span><?php endif; ?>
                                <br>
                                <?= htmlspecialchars($addr['phone']); ?><br>
                            <?= htmlspecialchars($display_address); ?>
                            </div>
                            <div class="address-actions">
                                <button type="button" class="btn-action edit" onclick="editAddress(this, event)" title="Edit Address">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="btn-action delete" onclick="deleteAddress(<?= $addr['id'] ?>, this, event)" title="Delete Address">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </label>
                        <?php endforeach; ?>
                        <label class="address-option add-new">
                            <input type="radio" name="address_selection" value="new">
                            <div class="address-details" style="padding:10px 0;">
                                <i class="fas fa-plus-circle" style="font-size:1.5rem; color:#4f46e5; margin-bottom:5px;"></i><br>
                                <strong>Add New Address</strong>
                            </div>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <h3 class="section-title">Shipping Details</h3>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" placeholder="+1 234 567 8900" required>
                    </div>
                    <div class="form-group">
                        <label>Shipping Address</label>
                        <textarea name="address" rows="3" placeholder="Street address, Apt, City, Zip Code" required></textarea>
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" name="set_default" id="set_default" style="width:auto;">
                        <label for="set_default" style="margin:0; cursor:pointer;">Set as default address</label>
                    </div>
                </div>

                <div class="card" style="margin-top: 25px;">
                    <h3 class="section-title">Payment Method</h3>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Credit Card">
                            <span class="payment-label">Credit / Debit Card</span>
                            <span class="payment-icons"><i class="far fa-credit-card"></i></span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="PayPal">
                            <span class="payment-label">PayPal</span>
                            <span class="payment-icons"><i class="fab fa-paypal"></i></span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="COD">
                            <span class="payment-label">Cash on Delivery</span>
                            <span class="payment-icons"><i class="fas fa-money-bill-wave"></i></span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="place_order" class="btn-place-order" disabled>
                    <i class="fas fa-lock"></i> Pay $<?= number_format($total, 2); ?> Securely
                </button>

                <div class="reassurance">
                    <span><i class="fas fa-shield-alt"></i> SSL Secure</span>
                    <span><i class="fas fa-undo"></i> 30-Day Returns</span>
                    <span><i class="fas fa-check-circle"></i> Best Price</span>
                </div>
            </form>
        </div>

        <!-- Right Column: Summary -->
        <div class="order-summary">
            <div class="card">
                <h3 class="section-title">Order Summary</h3>
                
                <?php foreach ($cart_items as $item): ?>
                <div class="summary-item">
                    <img src="../images/<?= htmlspecialchars($item['image']); ?>" alt="Product">
                    <div class="item-info">
                        <h4><?= htmlspecialchars($item['name']); ?></h4>
                        <div class="item-meta">Qty: <?= $item['quantity']; ?></div>
                    </div>
                    <div class="item-price">$<?= number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
                <?php endforeach; ?>

                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>$<?= number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping</span>
                        <span><?= $shipping == 0 ? '<span style="color:#10b981; background:rgba(16, 185, 129, 0.1); padding:2px 8px; border-radius:20px; font-size:0.8rem; font-weight:600;">Free Shipping</span>' : '$' . number_format($shipping, 2); ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div class="total-row" style="color: #10b981;">
                        <span>Discount</span>
                        <span>-$<?= number_format($discount, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row">
                        <span>Estimated Delivery</span>
                        <span style="font-weight:600; color:#1e293b"><?= date('M d, Y', strtotime('+3 days')); ?></span>
                    </div>
                    <div class="total-row final">
                        <span>Total</span>
                        <span>$<?= number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteAddress(addressId, buttonElement, event) {
            event.preventDefault();
            event.stopPropagation();

            if (!confirm('Are you sure you want to delete this saved address?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_address');
            formData.append('address_id', addressId);

            fetch('checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Visually remove the address from the list
                    buttonElement.closest('.address-option').remove();
                } else {
                    alert('Error: ' + (data.message || 'Could not delete address.'));
                }
            })
            .catch(error => alert('An error occurred. Please try again.'));
        }

        function editAddress(button, event) {
            event.preventDefault();
            event.stopPropagation();
            
            const label = button.closest('.address-option');
            const radio = label.querySelector('input[type="radio"]');
            
            // Select the radio and fill form
            radio.checked = true;
            fillAddress(radio);
            
            // Update visual selection
            document.querySelectorAll('.address-option').forEach(el => el.classList.remove('selected'));
            label.classList.add('selected');

            // Scroll to form and focus
            document.querySelector('input[name="full_name"]').focus();
            document.querySelector('.checkout-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Handle Address Selection Logic
        const addressRadios = document.querySelectorAll('input[name="address_selection"]');
        addressRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Visual Selection
                document.querySelectorAll('.address-option').forEach(el => el.classList.remove('selected'));
                this.closest('.address-option').classList.add('selected');

                // Form Logic
                if (this.value === 'new') {
                    clearAddress();
                } else {
                    fillAddress(this);
                }
            });
        });

        function fillAddress(radio) {
            document.querySelector('input[name="full_name"]').value = radio.dataset.name;
            document.querySelector('input[name="phone"]').value = radio.dataset.phone;
            document.querySelector('textarea[name="address"]').value = radio.dataset.address;
            document.getElementById('set_default').checked = false;
        }

        function clearAddress() {
            document.querySelector('input[name="full_name"]').value = '';
            document.querySelector('input[name="phone"]').value = '';
            document.querySelector('textarea[name="address"]').value = '';
            document.getElementById('set_default').checked = true;
        }

        const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
        const submitBtn = document.querySelector('.btn-place-order');
        const totalAmount = "<?= number_format($total, 2); ?>";

        function updatePaymentButton(radio) {
            submitBtn.disabled = false;
            if (radio.value === 'COD') {
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Place Order';
            } else {
                submitBtn.innerHTML = '<i class="fas fa-lock"></i> Pay $' + totalAmount + ' Securely';
            }
        }

        paymentRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                updatePaymentButton(this);
            });
        });

        // Initialize form with default selection if exists
        window.addEventListener('DOMContentLoaded', () => {
            const checkedRadio = document.querySelector('input[name="address_selection"]:checked');
            if (checkedRadio && checkedRadio.value !== 'new') {
                fillAddress(checkedRadio);
            }
            
            const checkedPayment = document.querySelector('input[name="payment_method"]:checked');
            if (checkedPayment) {
                updatePaymentButton(checkedPayment);
            }
        });
    </script>
</body>
</html>
