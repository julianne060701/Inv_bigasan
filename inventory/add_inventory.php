<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rice_type = $_POST['rice_type'];
    $price_per_kg = $_POST['price_per_kg'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $category_id = $_POST['category'];
    $alert_threshold = $_POST['alert_threshold'];

    $stmt = $conn->prepare("INSERT INTO rice_inventory (rice_type, price_per_kg, quantity, unit, category_id, alert_threshold) VALUES (?, ?, ?, ?, ?,?)");
    $stmt->bind_param("sdisii", $rice_type, $price_per_kg, $quantity, $unit, $category_id, $alert_threshold);

    if ($stmt->execute()) {
        header("Location: inventory.php?success=1");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
