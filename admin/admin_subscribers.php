<?php
session_start();
include '../includes/db.php';

// 1. Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Handle Delete Action (Secure POST)
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_subscribers.php?msg=deleted");
    exit();
}

// 3. Fetch Subscribers
$stmt = $conn->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC");
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Newsletter Subscribers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Page Specific Styles */
        .content-header { margin-bottom: 48px; }
        .content-header h1 { 
            color: #111827; 
            margin: 0 0 8px 0; 
            font-size: 1.85rem; 
            font-weight: 700; 
            letter-spacing: -0.03em; 
        }
        .content-header p {
            color: #6B7280;
            margin: 0;
            font-size: 1rem;
        }
        
        .table-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            padding: 8px;
            border: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 24px 32px;
            text-align: left;
        }
        th {
            background: #34495e;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            white-space: nowrap;
            border-bottom: none;
        }
        td {
            color: #4a5568;
            font-size: 1rem;
            vertical-align: middle;
            white-space: nowrap;
            border-bottom: 1px solid #f0f2f5;
        }
        tr:last-child td { border-bottom: none; }
        tr { transition: background 0.2s ease; }
        tr:hover { background: #f8f9fa; }
        
        /* ID Column - Muted */
        td:first-child { color: #bdc3c7; font-variant-numeric: tabular-nums; }
        /* Email Column - Stronger */
        td:nth-child(2) { font-weight: 500; color: #2d3748; }
        
        .btn-delete {
            background: transparent;
            color: #95a5a6;
            border: none;
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-delete:hover { 
            background: #fdfbf7; 
            color: #e67e22; 
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .btn-delete:active { transform: scale(0.95); }
        
        .empty-msg { text-align: center; padding: 80px; color: #95a5a6; background: #ffffff; border-radius: 24px; border: 1px dashed #bdc3c7; }
        .alert { 
            padding: 16px 24px; 
            background: #FFFBEB; 
            color: #92400E; 
            border: 1px solid #FEF3C7; 
            border-radius: 12px; 
            margin-bottom: 32px; 
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content-header">
                <h1>Newsletter Subscribers</h1>
                <p>Manage your email list and audience growth.</p>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert"><i class="fas fa-check-circle"></i> Subscriber removed successfully.</div>
            <?php endif; ?>

            <?php if (empty($subscribers)): ?>
                <div class="empty-msg">No subscribers found.</div>
            <?php else: ?>
                <div class="table-card" style="overflow-x: auto;">
                <table>
                    <tr><th>ID</th><th>Email Address</th><th>Subscribed At</th><th>Action</th></tr>
                    <?php foreach ($subscribers as $sub): ?>
                    <tr>
                        <td><?= htmlspecialchars($sub['id']); ?></td>
                        <td><?= htmlspecialchars($sub['email']); ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($sub['subscribed_at'])); ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to remove this email?');">
                                <input type="hidden" name="delete_id" value="<?= $sub['id']; ?>">
                                <button type="submit" class="btn-delete" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>