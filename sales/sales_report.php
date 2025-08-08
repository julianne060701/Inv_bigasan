<?php 
session_start();  // Start session before any output 
 
// Redirect if not logged in as admin 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); 
    exit(); 
} 
 
include '../config/conn.php'; 

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'overall';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build date condition based on filter
$date_condition = "";
$params = [];
$param_types = "";

switch($filter) {
    case 'today':
        $date_condition = "WHERE DATE(date_of_sale) = CURDATE()";
        break;
    case 'week':
        $date_condition = "WHERE date_of_sale >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "WHERE MONTH(date_of_sale) = MONTH(CURDATE()) AND YEAR(date_of_sale) = YEAR(CURDATE())";
        break;
    case 'year':
        $date_condition = "WHERE YEAR(date_of_sale) = YEAR(CURDATE())";
        break;
    case 'custom':
        if($start_date && $end_date) {
            $date_condition = "WHERE date_of_sale BETWEEN ? AND ?";
            $params = [$start_date, $end_date];
            $param_types = "ss";
        }
        break;
    case 'overall':
    default:
        $date_condition = "";
        break;
}

// Query for sales summary
$summary_query = "SELECT 
    COUNT(*) as total_transactions,
    SUM(quantity_sold) as total_quantity_sold,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_transaction
FROM sales $date_condition";

if(!empty($params)) {
    $stmt = $conn->prepare($summary_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();
} else {
    $result = $conn->query($summary_query);
    $summary = $result->fetch_assoc();
}

// Query for detailed sales data
$sales_query = "SELECT 
    s.sale_id,
    s.rice_type,
    s.quantity_sold,
    s.unit,
    s.price_per_kg,
    s.total_amount,
    s.date_of_sale,
    s.cashier
FROM sales s 
$date_condition 
ORDER BY s.date_of_sale DESC, s.sale_id DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $sales_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($sales_query);
    $sales_data = $result->fetch_all(MYSQLI_ASSOC);
}

// Query for rice type breakdown
$breakdown_query = "SELECT 
    rice_type,
    COUNT(*) as transaction_count,
    SUM(quantity_sold) as total_quantity,
    SUM(total_amount) as total_revenue
FROM sales 
$date_condition 
GROUP BY rice_type 
ORDER BY total_revenue DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($breakdown_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $breakdown_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($breakdown_query);
    $breakdown_data = $result->fetch_all(MYSQLI_ASSOC);
}

// Query for daily sales chart (last 30 days for chart)
$chart_query = "SELECT 
    DATE(date_of_sale) as sale_date,
    SUM(total_amount) as daily_revenue,
    SUM(quantity_sold) as daily_quantity
FROM sales 
WHERE date_of_sale >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(date_of_sale) 
ORDER BY sale_date ASC";

$result = $conn->query($chart_query);
$chart_data = $result->fetch_all(MYSQLI_ASSOC);
?> 
<!DOCTYPE html> 
<html lang="en"> 
 
<head> 
    <?php include('../includes/header.php'); ?> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filter-card {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .filter-btn {
            margin: 0.25rem;
        }
        .filter-btn.active {
            background-color: #4e73df !important;
            border-color: #4e73df !important;
            color: white !important;
        }
        .summary-card {
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-2px);
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
    </style>
</head> 
 
<body id="page-top"> 
 
    <!-- Page Wrapper --> 
    <div id="wrapper"> 
 
        <!-- Sidebar --> 
        <?php include('../includes/sidebar.php'); ?> 
        <!-- End of Sidebar --> 
 
        <!-- Content Wrapper --> 
        <div id="content-wrapper" class="d-flex flex-column"> 
 
            <!-- Main Content --> 
            <div id="content"> 
 
                <!-- Topbar --> 
                <?php include('../includes/topbar.php'); ?> 
                <!-- End of Topbar --> 
 
                <!-- Begin Page Content --> 
                <div class="container-fluid"> 
 
                    <!-- Page Heading --> 
                    <div class="d-sm-flex align-items-center justify-content-between mb-4"> 
                        <h1 class="h3 mb-0 text-gray-800">Sales Report</h1>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-card">
                        <h5 class="mb-3">Filter Sales Data</h5>
                        <form method="GET" id="filterForm">
                            <div class="row align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label">Time Period:</label><br>
                                    <button type="submit" name="filter" value="today" class="btn btn-outline-primary filter-btn <?= $filter == 'today' ? 'active' : '' ?>">Today</button>
                                    <button type="submit" name="filter" value="week" class="btn btn-outline-primary filter-btn <?= $filter == 'week' ? 'active' : '' ?>">This Week</button>
                                    <button type="submit" name="filter" value="month" class="btn btn-outline-primary filter-btn <?= $filter == 'month' ? 'active' : '' ?>">This Month</button>
                                    <button type="submit" name="filter" value="year" class="btn btn-outline-primary filter-btn <?= $filter == 'year' ? 'active' : '' ?>">This Year</button>
                                    <button type="submit" name="filter" value="overall" class="btn btn-outline-primary filter-btn <?= $filter == 'overall' ? 'active' : '' ?>">Overall</button>
                                    <button type="button" class="btn btn-outline-secondary filter-btn" onclick="toggleCustomDate()">Custom Range</button>
                                </div>
                            </div>
                            
                            <!-- Custom Date Range -->
                            <div id="customDateRange" class="row mt-3" style="display: <?= $filter == 'custom' ? 'flex' : 'none' ?>">
                                <div class="col-md-3">
                                    <label class="form-label">Start Date:</label>
                                    <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Date:</label>
                                    <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="filter" value="custom" class="btn btn-primary">Apply Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
 
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2 summary-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Revenue
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                ₱<?= number_format($summary['total_revenue'] ?? 0, 2) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-peso-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 summary-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Transactions
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($summary['total_transactions'] ?? 0) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-receipt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2 summary-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Total Quantity Sold
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($summary['total_quantity_sold'] ?? 0, 2) ?> kg
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-weight fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2 summary-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Average Transaction
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                ₱<?= number_format($summary['avg_transaction'] ?? 0, 2) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <!-- Sales Trend Chart -->
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Sales Trend (Last 30 Days)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="salesTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rice Type Breakdown -->
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Rice Type Sales</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="riceTypeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rice Type Breakdown Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Sales by Rice Type</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Rice Type</th>
                                                    <th>Transactions</th>
                                                    <th>Total Quantity (kg)</th>
                                                    <th>Total Revenue</th>
                                                    <th>Avg Price/kg</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($breakdown_data)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No sales data available for the selected period</td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach($breakdown_data as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['rice_type']) ?></td>
                                                    <td><?= number_format($row['transaction_count']) ?></td>
                                                    <td><?= number_format($row['total_quantity'], 2) ?></td>
                                                    <td>₱<?= number_format($row['total_revenue'], 2) ?></td>
                                                    <td>₱<?= $row['total_quantity'] > 0 ? number_format($row['total_revenue'] / $row['total_quantity'], 2) : '0.00' ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Sales Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Detailed Sales Transactions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Sale ID</th>
                                                    <th>Date</th>
                                                    <th>Rice Type</th>
                                                    <th>Quantity</th>
                                                    <th>Unit</th>
                                                    <th>Price/kg</th>
                                                    <th>Total Amount</th>
                                                    <th>Cashier</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($sales_data)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No sales data available for the selected period</td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach($sales_data as $sale): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($sale['sale_id']) ?></td>
                                                    <td><?= date('M j, Y g:i A', strtotime($sale['date_of_sale'])) ?></td>
                                                    <td><?= htmlspecialchars($sale['rice_type']) ?></td>
                                                    <td><?= number_format($sale['quantity_sold'], 2) ?></td>
                                                    <td><?= htmlspecialchars($sale['unit']) ?></td>
                                                    <td>₱<?= number_format($sale['price_per_kg'], 2) ?></td>
                                                    <td>₱<?= number_format($sale['total_amount'], 2) ?></td>
                                                    <td><?= htmlspecialchars($sale['cashier']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
 
                </div> 
                <!-- /.container-fluid --> 
 
            </div> 
            <!-- End of Main Content --> 
 
            <!-- Footer --> 
            <?php include('../includes/footer.php'); ?> 
            <!-- End of Footer --> 
 
        </div> <!-- End of Content Wrapper --> 
 
    </div> <!-- End of Page Wrapper --> 

    <script>
        function toggleCustomDate() {
            const customRange = document.getElementById('customDateRange');
            customRange.style.display = customRange.style.display === 'none' ? 'flex' : 'none';
        }

        // Sales Trend Chart
        const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M j', strtotime($item['sale_date'])) . "'"; }, $chart_data)); ?>],
                datasets: [{
                    label: 'Daily Revenue',
                    data: [<?php echo implode(',', array_column($chart_data, 'daily_revenue')); ?>],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Rice Type Pie Chart
        const pieCtx = document.getElementById('riceTypeChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . htmlspecialchars($item['rice_type']) . "'"; }, $breakdown_data)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($breakdown_data, 'total_revenue')); ?>],
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ₱' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
 
</body> 
 
</html>