<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $image = $_FILES['image'];

    // 1. Backend Validation
    if (empty($name) || empty($price) || empty($description) || empty($image['name'])) {
        $error = "All fields are required.";
    } elseif (!is_numeric($price)) {
        $error = "Price must be a valid number.";
    } else {
        // 2. Image Validation & Renaming
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $image['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            $error = "Invalid image type. Only JPG, PNG, and WEBP are allowed.";
        } else {
            // Rename image to avoid duplicates
            $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
            $new_name = uniqid('prod_', true) . "." . $ext;
            $target = "../images/" . $new_name;

            if (move_uploaded_file($image['tmp_name'], $target)) {
                // 3. Secure Insertion
                try {
                    $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $price, $description, $new_name]);
                    
                    // 4. Redirect on Success
                    header("Location: manage_products.php?msg=added");
                    exit();
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            } else {
                $error = "Failed to upload image.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f1f5f9 !important; /* Slate 100 */
            background-image: none !important;
        }
        .main-content {
            padding: 40px !important;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .split-wrapper {
            display: block;
            width: 100%;
            max-width: 1000px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #f1f5f9;
            padding: 40px 50px;
            animation: fadeIn 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        }
        
        /* Right Form Side */
        .form-side {
            width: 100%;
        }
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .form-header h2 {
            font-size: 1.75rem;
            color: #1e293b;
            margin: 0;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .btn-cancel {
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 12px;
            transition: all 0.2s;
        }
        .btn-cancel:hover { background: #f1f5f9; color: #0f172a; }

        /* Form Sections */
        .form-section { margin-bottom: 0; }
        .section-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 5px;
        }
        .form-group { margin-bottom: 24px; }
        label { 
            display: block; 
            margin-bottom: 6px; 
            color: #334155; 
            font-weight: 500; 
            font-size: 0.95rem; 
        }
        input, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            border-radius: 12px;
            font-size: 1rem;
            color: #1e293b;
            transition: all 0.3s ease;
            box-sizing: border-box;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        input:focus, textarea:focus { 
            border-color: #6366f1;
            outline: none; 
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            transform: translateY(-2px);
        }
        input::placeholder, textarea::placeholder { color: #94a3b8; }
        
        .btn-submit {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.3);
            margin-top: 10px;
        }
        .btn-submit:hover { 
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.4);
        }
        
        .error-msg { 
            color: #991b1b; 
            background: #fef2f2; 
            padding: 10px; 
            border-radius: 12px; 
            margin-bottom: 30px; 
            text-align: center; 
            border: 1px solid #fecaca; 
            font-weight: 500;
            font-size: 0.9em;
        }
        
        .img-preview {
            width: 100%;
            height: 100%;
            min-height: 300px;
            background: #f8fafc;
            border: 2px dashed #cbd5e1; /* Muted blue-gray */
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2px;
            overflow: hidden;
            color: #64748b;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            flex-direction: column;
        }
        .img-preview:hover { 
            border-color: #6366f1; 
            background: #eff6ff; 
            color: #6366f1;
        }
        .img-preview img { width: 100%; height: 100%; object-fit: cover; }
        .img-preview i { font-size: 2em; margin-bottom: 5px; color: inherit; }
        .img-preview span { font-weight: 500; font-size: 0.9em; }
        
        /* Hide default file input but keep it functional */
        input[type="file"] {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            border: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 40px;
        }

        /* Responsive */
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
            <div class="split-wrapper">

                <!-- Right Form Side -->
                <div class="form-side">
                    <div class="form-header">
                        <h2>Add Product</h2>
                        <a href="manage_products.php" class="btn-cancel">Cancel</a>
                    </div>

                <?php if ($error): ?>
                    <div class="error-msg"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-col-left">
                                <div class="form-group">
                                    <label>Product Name</label>
                                    <input type="text" name="name" required placeholder="e.g. Wireless Headphones">
                                </div>
                                <div class="form-group">
                                    <label>Price ($)</label>
                                    <input type="number" name="price" step="0.01" required placeholder="0.00">
                                </div>

                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" rows="5" required placeholder="Describe your product..."></textarea>
                                </div>
                        </div>

                        <div class="form-col-right">
                                <div class="form-group">
                                    <label>Product Image</label>
                                    <label for="file-upload" class="img-preview" id="preview-box">
                                        <div style="text-align: center;">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <div style="margin-top: 10px;">Click to Upload Image</div>
                                        </div>
                                    </label>
                                    <input id="file-upload" type="file" name="image" accept="image/*" required onchange="previewImage(this)">
                                </div>
                        </div>
                    </div>

                    <button type="submit" name="add_product" class="btn-submit">Publish Product</button>
                </form>
            </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const previewBox = document.getElementById('preview-box');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewBox.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                previewBox.innerHTML = '<span>Image Preview</span>';
            }
        }
    </script>
</body>
</html>