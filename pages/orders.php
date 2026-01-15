<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Orders
try {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $orders = [];
    $error = "Could not fetch orders.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .btn-back {
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }
        .btn-back:hover { color: #4f46e5; }

        .order-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .order-header {
            background: #f8fafc;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .order-meta {
            display: flex;
            gap: 30px;
        }

        .meta-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .meta-value {
            font-weight: 600;
            color: #1e293b;
        }

        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .status-processing { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
        .status-shipped { background: #f0f9ff; color: #0369a1; border: 1px solid #e0f2fe; }
        .status-delivered { background: #ecfdf5; color: #15803d; border: 1px solid #dcfce7; }
        .status-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }

        .order-items {
            padding: 24px;
        }

        .item-row {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .item-row:last-child { border-bottom: none; padding-bottom: 0; }
        .item-row:first-child { padding-top: 0; }

        .item-image {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .item-info { flex: 1; }
        .item-name { font-weight: 600; color: #1e293b; margin-bottom: 4px; }
        .item-qty { color: #64748b; font-size: 0.9rem; }
        .item-price { font-weight: 600; color: #1e293b; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        .empty-state i { font-size: 3rem; margin-bottom: 20px; color: #cbd5e1; }
        .btn-shop {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #4f46e5;
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-shop:hover { background: #4338ca; }

        @media (max-width: 640px) {
            .order-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .order-meta { flex-wrap: wrap; gap: 20px; }
            .order-status { align-self: flex-start; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h1>My Orders</h1>
        <a href="../index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Store</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h2>No orders yet</h2>
            <p>Looks like you haven't placed any orders yet.</p>
            <a href="../index.php" class="btn-shop">Start Shopping</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
                // Fetch items for this order
                $stmt_items = $conn->prepare("
                    SELECT oi.*, p.name, p.image 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?
                ");
                $stmt_items->execute([$order['id']]);
                $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                
                $status_class = 'status-' . strtolower($order['status']);
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div class="order-meta">
                        <div class="meta-group">
                            <span class="meta-label">Order Placed</span>
                            <span class="meta-value"><?= date('M d, Y', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="meta-group">
                            <span class="meta-label">Total</span>
                            <span class="meta-value">$<?= number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="meta-group">
                            <span class="meta-label">Order #</span>
                            <span class="meta-value"><?= $order['id']; ?></span>
                        </div>
                    </div>
                    <div class="order-status <?= $status_class; ?>">
                        <?= htmlspecialchars($order['status']); ?>
                    </div>
                </div>
                <div class="order-items">
                    <?php foreach ($items as $item): ?>
                        <div class="item-row">
                            <img src="../images/<?= htmlspecialchars($item['image']); ?>" alt="<?= htmlspecialchars($item['name']); ?>" class="item-image">
                            <div class="item-info">
                                <div class="item-name"><?= htmlspecialchars($item['name']); ?></div>
                                <div class="item-qty">Qty: <?= $item['quantity']; ?></div>
                            </div>
                            <div class="item-price">$<?= number_format($item['price'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>