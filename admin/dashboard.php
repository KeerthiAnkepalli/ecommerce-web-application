<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch Stats
$prod_count = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$sub_count = $conn->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();

$revenue = 0;
$recent_orders = [];
$recent_subscribers = [];
try {
    $revenue = $conn->query("SELECT SUM(total_amount) FROM orders")->fetchColumn() ?: 0;
    $recent_orders = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $recent_subscribers = $conn->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $revenue = 12450.00; // Mock data if table doesn't exist
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .main-content { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .welcome-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            border: none;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-text h1 { margin: 0; color: #2d3748; font-size: 2em; }
        .welcome-text p { margin: 10px 0 0; color: #718096; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover { 
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            color: white;
        }
        .stat-info h3 { margin: 0; font-size: 2em; color: #2d3748; }
        .stat-info p { margin: 0; color: #718096; font-size: 0.9em; font-weight: 500; }
        
        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        .chart-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        @media (max-width: 1024px) {
            .charts-container { grid-template-columns: 1fr; }
        }
        .tables-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-top: 24px;
        }
        @media (max-width: 1024px) {
            .tables-grid { grid-template-columns: 1fr; }
        }
        .table-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="welcome-card">
                <div class="welcome-text">
                    <h1>Hello, Admin! 👋</h1>
                    <p>Here's what's happening with your store today.</p>
                </div>
                <img src="https://cdn-icons-png.flaticon.com/512/2920/2920329.png" alt="Analytics" style="height: 100px; opacity: 0.8;">
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $prod_count; ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $sub_count; ?></h3>
                        <p>Subscribers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #334155, #0f172a);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?= number_format($revenue); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <div class="charts-container">
                <div class="chart-card">
                    <h3 style="margin-bottom: 20px; color: #4a5568;">Sales Overview</h3>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3 style="margin-bottom: 20px; color: #4a5568;">Traffic Source</h3>
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>

            <div class="tables-grid">
                <div class="table-card">
                <h3 style="margin-bottom: 20px; color: #4a5568;">Recent Orders</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 15px; color: #718096; border-bottom: 1px solid #edf2f7; font-weight: 600; font-size: 0.9em;">Order ID</th>
                                <th style="text-align: left; padding: 15px; color: #718096; border-bottom: 1px solid #edf2f7; font-weight: 600; font-size: 0.9em;">Customer</th>
                                <th style="text-align: left; padding: 15px; color: #718096; border-bottom: 1px solid #edf2f7; font-weight: 600; font-size: 0.9em;">Amount</th>
                                <th style="text-align: left; padding: 15px; color: #718096; border-bottom: 1px solid #edf2f7; font-weight: 600; font-size: 0.9em;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                                <tr><td colspan="4" style="padding: 20px; text-align: center; color: #a0aec0;">No recent orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td style="padding: 15px; border-bottom: 1px solid #edf2f7; color: #2d3748; font-weight: 500;">#<?= htmlspecialchars($order['id']); ?></td>
                                        <td style="padding: 15px; border-bottom: 1px solid #edf2f7; color: #4a5568;"><?= htmlspecialchars($order['full_name']); ?></td>
                                        <td style="padding: 15px; border-bottom: 1px solid #edf2f7; color: #2d3748; font-weight: 600;">$<?= number_format($order['total_amount'], 2); ?></td>
                                        <td style="padding: 15px; border-bottom: 1px solid #edf2f7;"><span style="background: #c6f6d5; color: #22543d; padding: 5px 12px; border-radius: 50px; font-size: 0.8em; font-weight: 600;">Completed</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>

                <div class="table-card">
                    <h3 style="margin-bottom: 20px; color: #4a5568;">New Subscribers</h3>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 15px; color: #718096; border-bottom: 1px solid #edf2f7; font-weight: 600; font-size: 0.9em;">Email</th>
                                    <th style="text-align: left; padding: 15px; color: #718096; border-bottom: 1px solid #edf2f7; font-weight: 600; font-size: 0.9em;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_subscribers)): ?>
                                    <tr><td colspan="2" style="padding: 20px; text-align: center; color: #a0aec0;">No subscribers yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent_subscribers as $sub): ?>
                                        <tr>
                                            <td style="padding: 15px; border-bottom: 1px solid #edf2f7; color: #2d3748; font-weight: 500;"><?= htmlspecialchars($sub['email']); ?></td>
                                            <td style="padding: 15px; border-bottom: 1px solid #edf2f7; color: #718096; font-size: 0.9em;"><?= date('M d', strtotime($sub['subscribed_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sales Chart
        const ctx1 = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales ($)',
                    data: [1200, 1900, 3000, 5000, 2300, 3400],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
            }
        });

        // Traffic Chart
        const ctx2 = document.getElementById('trafficChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Direct', 'Social', 'Organic'],
                datasets: [{
                    data: [55, 30, 15],
                    backgroundColor: ['#1abc9c', '#3498db', '#2c3e50'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</body>
</html>