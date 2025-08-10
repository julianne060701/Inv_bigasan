<?php
session_start();  // Start session before any output

// Redirect if not logged in as admin
// Option 1: If you chose to use 'user_id' (recommended)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Option 2: If you chose to use 'id' instead, use this:
// if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../login.php");
//     exit();
// }

include '../config/conn.php';

// Initialize stats
$stats = [
    'total_sales_today' => 0,
    'transactions_today' => 0,
    'items_sold_today' => 0,
    'average_sale' => 0,
    'out_of_stock' => 0,
    'critical_stock' => 0,
    'low_stock' => 0,
    'total_value' => 0
];

// ===== SALES STATS =====

// Total Sales Today
$sql = "SELECT SUM(total_amount) AS total FROM sales WHERE DATE(date_of_sale) = CURDATE()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['total_sales_today'] = $row['total'] ?? 0;

// Transactions Today
$sql = "SELECT COUNT(*) AS total FROM sales WHERE DATE(date_of_sale) = CURDATE()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['transactions_today'] = $row['total'] ?? 0;

// Items Sold Today
$sql = "SELECT SUM(quantity_sold) AS total_items FROM sales WHERE DATE(date_of_sale) = CURDATE()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['items_sold_today'] = $row['total_items'] ?? 0;

// Average Sale
if ($stats['transactions_today'] > 0) {
    $stats['average_sale'] = $stats['total_sales_today'] / $stats['transactions_today'];
}

// ===== INVENTORY STATS =====

// Out of Stock
$sql = "SELECT COUNT(*) AS total FROM rice_inventory WHERE quantity_sacks = 0";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['out_of_stock'] = $row['total'] ?? 0;

// Critical Stock
$sql = "SELECT COUNT(*) AS total FROM rice_inventory 
        WHERE quantity_sacks > 0 AND quantity_sacks <= (alert_threshold / 2)";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['critical_stock'] = $row['total'] ?? 0;

// Low Stock
$sql = "SELECT COUNT(*) AS total FROM rice_inventory 
        WHERE quantity_sacks < alert_threshold AND quantity_sacks > (alert_threshold / 2)";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['low_stock'] = $row['total'] ?? 0;

// Total Inventory Value
$sql = "SELECT SUM((quantity_sacks * sack_weight_kg + quantity_kg) * price_per_kg) AS total_value FROM rice_inventory";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['total_value'] = $row['total_value'] ?? 0;

// ===== HIGHEST SELLING PRODUCTS =====
$highest_selling = [];
$sql = "SELECT rice_type, SUM(quantity_sold) AS total_sold 
        FROM sales 
        GROUP BY rice_type 
        ORDER BY total_sold DESC 
        LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $highest_selling[] = $row;
}

// ===== LATEST SALES =====
$latest_sales = [];
$sql = "SELECT rice_type, quantity_sold, unit, total_amount, date_of_sale, cashier 
        FROM sales 
        ORDER BY date_of_sale DESC 
        LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $latest_sales[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Inventory - Categories</title>

    <!-- Font Awesome -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../employee/css/employee.css">
</head>

<body id="page-top">
<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('../includes/topbar.php'); ?>

          <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    </div>

                    <!-- First Stats Row -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card success">
                                <div class="stats-icon" style="background: var(--success-color);">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="stats-text">
                                    <h6 class="text-muted mb-1">Total Sales Today</h6>
                                    <h4 class="mb-0">₱<?php echo number_format($stats['total_sales_today'], 2); ?></h4>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card info">
                                <div class="stats-icon" style="background: var(--info-color);">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stats-text">
                                    <h6 class="text-muted mb-1">Transactions Today</h6>
                                    <h4 class="mb-0"><?php echo $stats['transactions_today']; ?></h4>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card warning">
                                <div class="stats-icon" style="background: var(--warning-color);">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <div class="stats-text">
                                    <h6 class="text-muted mb-1">Items Sold</h6>
                                    <h4 class="mb-0"><?php echo $stats['items_sold_today']; ?></h4>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card" style="border-left-color: var(--secondary-color);">
                                <div class="stats-icon" style="background: var(--secondary-color);">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stats-text">
                                    <h6 class="text-muted mb-1">Average Sale</h6>
                                    <h4 class="mb-0">₱<?php echo number_format($stats['average_sale'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second Stats Row -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card border-left-danger h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Out of Stock</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['out_of_stock']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-circle fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card border-left-warning h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Critical Stock</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['critical_stock']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card border-left-info h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Low Stock Items</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['low_stock']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card border-left-success h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Value</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                ₱<?php echo number_format($stats['total_value'], 2); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-peso-sign fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row for Highest Selling Products and Latest Sales -->
                    <div class="row mb-4">
                        <!-- Highest Selling Products -->
                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white d-flex align-items-center">
                                    <i class="fas fa-trophy mr-2"></i>
                                    <strong>Top Selling Products</strong>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($highest_selling)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                            <p>No sales data available yet</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($highest_selling as $index => $product): ?>
                                            <div class="d-flex justify-content-between align-items-center py-2 <?php echo $index < count($highest_selling) - 1 ? 'border-bottom' : ''; ?>">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 30px; height: 30px; font-size: 12px; font-weight: bold;">
                                                        <?php echo $index + 1; ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 font-weight-bold"><?php echo htmlspecialchars($product['rice_type']); ?></h6>
                                                        <small class="text-muted">Rice Type</small>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="badge badge-success badge-pill px-3 py-2">
                                                        <?php echo number_format($product['total_sold']); ?> sold
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Latest Sales -->
                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white d-flex align-items-center">
                                    <i class="fas fa-clock mr-2"></i>
                                    <strong>Recent Transactions</strong>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($latest_sales)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-receipt fa-3x mb-3"></i>
                                            <p>No recent transactions</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($latest_sales as $index => $sale): ?>
                                            <div class="mb-3 p-3 bg-light rounded <?php echo $index < count($latest_sales) - 1 ? 'border-bottom' : ''; ?>">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="mb-1 font-weight-bold text-dark"><?php echo htmlspecialchars($sale['rice_type']); ?></h6>
                                                        <small class="text-muted">
                                                            <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($sale['cashier']); ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge badge-success">₱<?php echo number_format($sale['total_amount'], 2); ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-cube mr-1"></i>
                                                        <?php echo $sale['quantity_sold']; ?> <?php echo $sale['unit']; ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar mr-1"></i>
                                                        <?php echo date("M d,Y  ", strtotime($sale['date_of_sale'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

        <?php include('../includes/footer.php'); ?>
    </div>
</div>

<!-- Scripts -->

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
