<?php
session_start();
include '../includes/db.php';

// 1. Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Handle Delete Action
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_messages.php?msg=deleted");
    exit();
}

// 3. Fetch Messages
$messages = [];
try {
    $stmt = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Contact Messages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .content-header { margin-bottom: 48px; }
        .content-header h1 { color: #111827; margin: 0 0 8px 0; font-size: 1.85rem; font-weight: 700; letter-spacing: -0.03em; }
        .content-header p { color: #6B7280; margin: 0; font-size: 1rem; }
        
        .table-card { background: #ffffff; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); padding: 8px; border: none; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 24px 32px; text-align: left; }
        th { background: #34495e; color: #ffffff; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; white-space: nowrap; }
        td { color: #4a5568; font-size: 1rem; vertical-align: top; border-bottom: 1px solid #f0f2f5; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f8f9fa; }
        
        .message-content { max-width: 400px; line-height: 1.6; color: #4a5568; }
        .meta-info { font-size: 0.85rem; color: #718096; margin-bottom: 5px; }
        
        .btn-delete {
            background: transparent; color: #95a5a6; border: none; width: 40px; height: 40px;
            border-radius: 12px; cursor: pointer; transition: all 0.2s ease; font-size: 1.1rem;
        }
        .btn-delete:hover { background: #fdfbf7; color: #e67e22; transform: scale(1.1); }
        
        .empty-msg { text-align: center; padding: 80px; color: #95a5a6; background: #ffffff; border-radius: 24px; border: 1px dashed #bdc3c7; }
        .alert { padding: 16px 24px; background: #FFFBEB; color: #92400E; border: 1px solid #FEF3C7; border-radius: 12px; margin-bottom: 32px; font-weight: 500; display: flex; align-items: center; gap: 12px; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content-header">
                <h1>Contact Messages</h1>
                <p>View inquiries sent from the contact form.</p>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert"><i class="fas fa-check-circle"></i> Message deleted successfully.</div>
            <?php endif; ?>

            <?php if (empty($messages)): ?>
                <div class="empty-msg">
                    <i class="far fa-envelope-open" style="font-size: 3rem; margin-bottom: 20px; display: block; opacity: 0.5;"></i>
                    No messages found.
                </div>
            <?php else: ?>
                <div class="table-card" style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Sender</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td style="white-space: nowrap;">
                            <div style="font-weight: 600; color: #2d3748;"><?= htmlspecialchars($msg['name']); ?></div>
                            <div style="font-size: 0.9em; color: #718096;"><?= htmlspecialchars($msg['email']); ?></div>
                        </td>
                        <td><div class="message-content"><?= nl2br(htmlspecialchars($msg['message'])); ?></div></td>
                        <td style="white-space: nowrap; color: #718096; font-size: 0.9em;"><?= isset($msg['created_at']) ? date('M d, Y h:i A', strtotime($msg['created_at'])) : 'N/A'; ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                <input type="hidden" name="delete_id" value="<?= $msg['id']; ?>">
                                <button type="submit" class="btn-delete" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>