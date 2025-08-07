<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve values from the form
    $product_name = $_POST['product_name'];
    $buying_price = $_POST['buying_price'];
    $quantity = $_POST['quantity'];
    $category_id = $_POST['category_id'];
    $selling_price = $_POST['selling_price'];

    // Prepare the INSERT statement
    $stmt = $conn->prepare("INSERT INTO products (product_name, buying_price, quantity, category_id, selling_price) VALUES (?, ?, ?, ?, ?)");
    
    // Bind parameters: s = string, d = double (float), i = integer
    $stmt->bind_param("sdiii", $product_name, $buying_price, $quantity, $category_id, $selling_price);

    if ($stmt->execute()) {
        header("Location: product.php?success=1");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
