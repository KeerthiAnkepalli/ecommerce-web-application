<?php
session_start();
include '../includes/db.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;

// Handle the form submission for updating the product
if (isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $current_image = $_POST['current_image'];
    $new_image = $_FILES['image']['name'];

    $image_to_update = $current_image;

    // Check if a new image was uploaded
    if (!empty($new_image)) {
        $target_dir = "../images/";
        $target_file = $target_dir . basename($new_image);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_to_update = $new_image;
        }
    }

    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, image = ? WHERE id = ?");
    if ($stmt->execute([$name, $price, $description, $image_to_update, $id])) {
        header("Location: manage_products.php");
        exit();
    } else {
        $errorMessage = "Failed to update product.";
    }
}

// Fetch the product details to pre-fill the form
$product = null;
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Poppins', sans-serif;
            color: #334155;
        }
        .main-content {
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 80px);
        }
        .form-card {
            background: #ffffff;
            padding: 40px 50px;
            width: 100%;
            max-width: 1000px;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #f1f5f9;
            animation: fadeIn 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h2 {
            color: #1e293b;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: -0.025em;
        }
        form { display: flex; flex-direction: column; }
        .form-group { margin-bottom: 24px; }
        label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-weight: 600;
            font-size: 0.9rem;
        }
        input[type="text"], input[type="number"], textarea, input[type="file"] {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        input:focus, textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            transform: translateY(-2px);
            outline: none;
        }
        input[type="file"] {
            padding: 10px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            cursor: pointer;
        }
        input[type="file"]::file-selector-button {
            background: #e2e8f0;
            color: #475569;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            margin-right: 12px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        input[type="file"]::file-selector-button:hover {
            background: #cbd5e1;
        }
        textarea { resize: vertical; min-height: 120px; }
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        button {
            flex: 1;
            padding: 14px 20px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            border:none;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.3);
        }
        button:hover {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.4);
        }
        .btn-cancel {
            flex: 1;
            padding: 14px 20px;
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-cancel:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-delete {
            flex: 1;
            padding: 14px 20px;
            background: #ffffff;
            color: #d97706;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-delete:hover {
            background: #fffbeb;
            border-color: #fcd34d;
            color: #b45309;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .current-image { 
            margin-top: 15px; 
            padding: 20px;
            background: #f8fafc;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .current-image span {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .current-image img { 
            max-width: 100%;
            max-height: 200px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            object-fit: contain;
        }
        .back-link { text-align: center; margin-top: 30px; }
        .back-link a { 
            color: #64748b; 
            text-decoration: none; 
            font-weight: 500; 
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        .back-link a:hover { color: #0f172a; }
        .error-msg-js {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .error-msg-js::before {
            content: '\f06a';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
        }
        .btn-primary-link {
            display: inline-flex;
            padding: 14px 20px;
            background: #0f172a;
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-primary-link:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 40px;
        }
        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <?php if ($product): ?>
            <div class="form-card">
        <h2>Edit Product</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($product['image']); ?>">
            
            <div class="form-grid">
            <div class="form-col-left">
            <div class="form-group">
            <label for="name">Product Name:</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
            <label for="price">Price:</label>
            <input type="number" step="0.01" name="price" id="price" value="<?= htmlspecialchars($product['price']); ?>" required>
            </div>

            <div class="form-group">
            <label for="description">Description:</label>
            <textarea name="description" id="description" rows="4" required><?= htmlspecialchars($product['description']); ?></textarea>
            </div>
            </div>

            <div class="form-col-right">

            <div class="form-group">
            <label for="image">Image:</label>
            <input type="file" name="image" id="image">
            <div class="current-image">
                <span>Current Image</span>
                <img src="../images/<?= htmlspecialchars($product['image']); ?>" alt="Current Image">
            </div>
            </div>
            </div>
            </div>

            <div class="form-actions">
                <a href="delete_product.php?id=<?= $id; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?');"><i class="fas fa-trash-alt"></i> Delete</a>
                <a href="manage_products.php" class="btn-cancel">Cancel</a>
                <button type="submit" name="update_product">Update Product</button>
            </div>
        </form>
        <div class="back-link">
            <a href="manage_products.php"><i class="fas fa-arrow-left"></i> Back to Manage Products</a>
        </div>
            </div>
            <?php else: ?>
            <div class="form-card" style="text-align: center; max-width: 500px;">
                <h2>Edit Product</h2>
                <div style="margin: 40px 0; color: #cbd5e1;">
                    <i class="fas fa-edit" style="font-size: 4rem;"></i>
                </div>
                <p style="color: #64748b; margin-bottom: 30px; font-size: 1.1rem;">Please select a product from the list to edit.</p>
                <a href="manage_products.php" class="btn-primary-link">Go to Product List</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input, textarea');

            form.addEventListener('submit', function(e) {
                let isValid = true;
                clearErrors();

                const name = document.getElementById('name');
                const price = document.getElementById('price');
                const description = document.getElementById('description');
                // Image is optional in edit mode

                if (name.value.trim() === '') {
                    showError(name, 'Product name is required.');
                    isValid = false;
                }

                if (price.value === '' || parseFloat(price.value) <= 0) {
                    showError(price, 'Please enter a valid positive price.');
                    isValid = false;
                }

                if (description.value.trim() === '') {
                    showError(description, 'Description is required.');
                    isValid = false;
                }

                if (!isValid) e.preventDefault();
            });

            function showError(input, message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-msg-js';
                errorDiv.innerText = message;
                input.style.borderColor = '#ef4444';
                input.parentNode.insertBefore(errorDiv, input.nextSibling);
            }

            function clearErrors() {
                document.querySelectorAll('.error-msg-js').forEach(el => el.remove());
                inputs.forEach(input => input.style.borderColor = '#e2e8f0');
            }

            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    const nextEl = this.nextElementSibling;
                    if (nextEl && nextEl.classList.contains('error-msg-js')) {
                        nextEl.remove();
                        this.style.borderColor = '#e2e8f0';
                    }
                });
            });
        });
    </script>
</body>
</html>