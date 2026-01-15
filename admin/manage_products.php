<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include '../includes/db.php';

// Pagination Configuration
$limit = 5; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch products with pagination
if ($search) {
    $sql = "SELECT * FROM products WHERE name LIKE :search OR description LIKE :search LIMIT :start, :limit";
    $count_sql = "SELECT COUNT(*) FROM products WHERE name LIKE :search OR description LIKE :search";
} else {
    $sql = "SELECT * FROM products LIMIT :start, :limit";
    $count_sql = "SELECT COUNT(*) FROM products";
}

$stmt = $conn->prepare($sql);
if ($search) {
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}
$stmt->bindParam(':start', $start, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of products for pagination links
$total_stmt = $conn->prepare($count_sql);
if ($search) {
    $total_stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}
$total_stmt->execute();
$total_rows = $total_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .content-header h2 { color: #2d3748; margin: 0; font-weight: 700; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }
        th, td {
            padding: 18px 24px;
            text-align: left;
            border-bottom: 1px solid #f0f2f5;
        }
        th {
            background: #34495e;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8em;
            letter-spacing: 0.05em;
        }
        tr { transition: all 0.2s ease; }
        tr:hover { 
            background: #ffffff; 
            transform: scale(1.01); 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 10;
            position: relative;
        }
        
        td img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn {
            padding: 6px 12px;
            border-radius: 10px;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            color: #4b5563;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            margin: 0 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            min-width: 80px;
        }

        /* Add New */
        .btn-add {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }
        .btn-add:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.4);
        }

        /* Edit */
        .btn-edit {
            background-color: #ffffff;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }
        .btn-edit:hover {
            background-color: #f8fafc;
            border-color: #cbd5e0;
        }

        /* Delete */
        .btn-delete {
            background: linear-gradient(135deg, #475569 0%, #334155 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(30, 41, 59, 0.3);
        }
        .btn-delete:hover {
            background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px -1px rgba(30, 41, 59, 0.4);
        }

        .search-container {
            margin-bottom: 20px;
            text-align: right;
        }
        #searchInput {
            padding: 10px 15px;
            width: 300px;
            max-width: 100%;
            border-radius: 50px;
            border: 1px solid #dfe6e9;
            background: #ffffff;
            color: #333;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        #searchInput:focus {
            border-color: #3498db;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .table-responsive {
            overflow-x: auto;
            border-radius: 20px;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .pagination a {
            color: #4a5568;
            padding: 8px 16px;
            text-decoration: none;
            border: none;
            border-radius: 50px;
            transition: all 0.3s ease;
            background: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .pagination a:hover {
            background: #ecf0f1;
            transform: translateY(-2px);
        }
        .pagination a.active {
            background: #3498db;
            color: #fff;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid transparent; }
        .alert.success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 16px;
            width: 400px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-actions {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .btn-modal-cancel {
            background: #f1f5f9;
            color: #64748b;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-modal-cancel:hover { background: #e2e8f0; }
        .btn-modal-delete {
            background: #475569;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-modal-delete:hover { background: #334155; }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h2>Manage Products</h2>
            <div>
                <button type="submit" form="bulkDeleteForm" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete the selected products?')" style="margin-right: 5px; padding: 8px 16px;"><i class="fas fa-trash"></i> Delete Selected</button>
                <a href="add_product.php" class="btn btn-add"><i class="fas fa-plus"></i> Add New</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'added'): ?>
                <div class="alert success">Product added successfully!</div>
            <?php elseif($_GET['msg'] == 'deleted'): ?>
                <div class="alert success">Product deleted successfully!</div>
            <?php elseif($_GET['msg'] == 'error'): ?>
                <div class="alert error">
                    <?= isset($_GET['info']) ? htmlspecialchars($_GET['info']) : 'An error occurred.'; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="search-container">
            <form method="GET" action="">
                <input type="text" name="search" id="searchInput" placeholder="Search products..." value="<?= htmlspecialchars($search); ?>">
            </form>
        </div>
        <form id="bulkDeleteForm" action="delete_product.php" method="POST">
        <div class="table-responsive">
    <table>
        <tr>
            <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll"></th>
            <th>ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Description</th>
            <th>Image</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($products as $index => $product) : ?>
            <tr>
                <td style="text-align: center;"><input type="checkbox" name="bulk_delete_ids[]" value="<?= $product['id']; ?>" class="product-checkbox"></td>
                <td><?= $start + $index + 1; ?></td>
                <td><?= htmlspecialchars($product['name']); ?></td>
                <td>$<?= number_format($product['price'], 2); ?></td>
                <td><?= htmlspecialchars($product['description']); ?></td>
                <td><img src="../images/<?= htmlspecialchars($product['image']); ?>" alt="Product Image"></td>
                <td class="actions">
                    <a href="edit_product.php?id=<?= $product['id']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                    <a href="javascript:void(0)" class="btn btn-delete" onclick="openDeleteModal(<?= $product['id']; ?>)"><i class="fas fa-trash-alt"></i> Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
    </form>

        <!-- Pagination Links -->
        <?php $search_param = $search ? '&search=' . urlencode($search) : ''; ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page - 1 . $search_param; ?>">Prev</a>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i . $search_param; ?>" class="<?= ($page == $i) ? 'active' : ''; ?>"><?= $i; ?></a>
            <?php endfor; ?>
            
            <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 . $search_param; ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div style="margin-bottom: 15px; color: #ef4444; font-size: 3rem;"><i class="fas fa-exclamation-circle"></i></div>
        <h3 style="color: #1e293b; margin: 0 0 10px 0;">Confirm Deletion</h3>
        <p style="color: #64748b; margin-bottom: 5px;">Are you sure you want to delete this product?</p>
        <p style="color: #94a3b8; font-size: 0.9em;">Note: Products with existing orders cannot be deleted.</p>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeModal('deleteModal')">Cancel</button>
            <a id="confirmDeleteBtn" href="#" class="btn-modal-delete">Yes, Delete</a>
        </div>
    </div>
</div>

<!-- Error/Info Modal -->
<div id="infoModal" class="modal">
    <div class="modal-content">
        <div style="margin-bottom: 15px; color: #3b82f6; font-size: 3rem;"><i class="fas fa-info-circle"></i></div>
        <h3 style="color: #1e293b; margin: 0 0 10px 0;">Action Failed</h3>
        <p id="infoText" style="color: #64748b; line-height: 1.5;"></p>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeModal('infoModal')">Close</button>
        </div>
    </div>
</div>

<script>
    // Select All Checkbox Logic
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    });

    function openDeleteModal(id) {
        document.getElementById('confirmDeleteBtn').href = 'delete_product.php?id=' + id;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Check for error messages from PHP URL parameters
    window.addEventListener('DOMContentLoaded', (event) => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('msg') && urlParams.get('msg') === 'error') {
            const info = urlParams.get('info') || 'An unknown error occurred.';
            document.getElementById('infoText').textContent = info;
            document.getElementById('infoModal').style.display = 'flex';
        }
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }
</script>

</body>
</html>