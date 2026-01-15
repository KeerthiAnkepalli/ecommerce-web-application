<?php
session_start();
include '../includes/db.php';

// Check if the admin is logged in, otherwise redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Bulk Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_delete_ids']) && is_array($_POST['bulk_delete_ids'])) {
        $ids = $_POST['bulk_delete_ids'];
        $error_msg = "";
        $deleted_count = 0;

        foreach ($ids as $id) {
            try {
                // Fetch product to get image
                $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    // Delete image
                    $imagePath = '../images/' . $product['image'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    // Delete record
                    $deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                    $deleteStmt->execute([$id]);
                    $deleted_count++;
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), '1451') !== false) {
                    $error_msg = "Some products could not be deleted because they have existing orders.";
                } else {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        $redirect_msg = $error_msg ? "msg=error&info=" . urlencode($error_msg) : "msg=deleted";
        header("Location: manage_products.php?" . $redirect_msg);
        exit();
    } else {
        header("Location: manage_products.php?msg=error&info=No items selected");
        exit();
    }
}

// Get the product ID from the URL
$id = $_GET['id'] ?? null;

// If no ID is provided, redirect back to the manage products page
if (!$id) {
    header("Location: manage_products.php?msg=error&info=No ID provided");
    exit();
}

try {
    // First, fetch the product to get the image filename
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Delete the associated image file from the 'images' folder
        $imagePath = '../images/' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Now, delete the product record from the database
        $deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $deleteStmt->execute([$id]);
        
        header("Location: manage_products.php?msg=deleted");
        exit();
    } else {
        header("Location: manage_products.php?msg=error&info=Product not found");
        exit();
    }
} catch (PDOException $e) {
    // Catch database errors (like foreign key constraints)
    $error = "Database error: " . $e->getMessage();
    if (strpos($e->getMessage(), '1451') !== false) {
        $error = "Cannot delete product: It is linked to existing orders.";
    }
    header("Location: manage_products.php?msg=error&info=" . urlencode($error));
    exit();
}