<?php
include 'config/db.php';
$result = $conn->query("SELECT * FROM rice_inventory WHERE quantity <= alert_threshold");

echo "<h3>Critical Stock Alerts</h3>";
while ($row = $result->fetch_assoc()) {
    echo "<p><strong>{$row['rice_type']}</strong> is low on stock: {$row['quantity']} {$row['unit']} left</p>";
}
?>
