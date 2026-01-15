<style>
    /* Sidebar & Layout Styles */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    body { 
        margin: 0; 
        font-family: 'Poppins', sans-serif; 
        background: #f0f2f5;
        color: #2c3e50;
        min-height: 100vh;
    }
    
    .admin-wrapper { display: flex; min-height: 100vh; }
    
    .sidebar {
        width: 280px;
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        color: #ecf0f1;
        border-right: none;
        display: flex;
        flex-direction: column;
        padding: 32px 24px;
        flex-shrink: 0;
        box-shadow: 5px 0 25px rgba(0,0,0,0.03);
    }
    .sidebar h2 {
        text-align: left;
        margin: 0 0 48px 12px;
        font-size: 1.1rem;
        font-weight: 700;
        color: #ecf0f1;
        letter-spacing: -0.025em;
        display: flex;
        align-items: center;
        gap: 10px;
        text-shadow: none;
    }
    .sidebar a {
        color: #bdc3c7;
        text-decoration: none;
        padding: 12px 16px;
        margin-bottom: 8px;
        border-radius: 12px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 14px;
        font-size: 0.95rem;
        font-weight: 500;
    }
    .sidebar a i { width: 20px; text-align: center; color: #95a5a6; transition: color 0.2s; }
    .sidebar a:hover {
        background: #34495e;
        color: #ffffff;
        transform: translateX(8px);
        box-shadow: none;
    }
    .sidebar a.active {
        background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    }
    .sidebar a.active i { color: #ffffff; }
    
    .sidebar a.logout {
        margin-top: 10px;
        color: #bdc3c7;
        background: transparent;
    }
    .sidebar a.logout:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
        transform: none;
        box-shadow: none;
    }
    .main-content { flex: 1; padding: 48px 64px; overflow-y: auto; }

    @media (max-width: 768px) {
        .admin-wrapper { flex-direction: column; }
        .sidebar { width: 100%; padding: 20px; border-right: none; border-bottom: 1px solid #34495e; }
        .sidebar h2 { margin-bottom: 24px; }
        .main-content { padding: 24px; }
    }
</style>

<div class="sidebar">
    <h2><i class="fas fa-cube" style="color: #3498db;"></i> Admin Panel</h2>
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> Manage Products</a>
    <a href="add_product.php" class="<?= basename($_SERVER['PHP_SELF']) == 'add_product.php' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> Add Product</a>
    <a href="edit_product.php" class="<?= basename($_SERVER['PHP_SELF']) == 'edit_product.php' ? 'active' : '' ?>"><i class="fas fa-edit"></i> Edit Products</a>
    <a href="admin_subscribers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin_subscribers.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Subscribers</a>
    <a href="admin_messages.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin_messages.php' ? 'active' : '' ?>"><i class="fas fa-comment-alt"></i> Messages</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>