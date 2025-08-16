<?php
include '../config/conn.php';
session_start(); // Needed for $_SESSION['username']

$success_message = '';
$error_message = '';

// Fetch from products table (aircon category) for dropdown
$aircon_sql = "SELECT p.*, c.category_name 
               FROM products p 
               LEFT JOIN category c ON p.category_id = c.category_id 
               WHERE LOWER(c.category_name) LIKE '%aircon%' 
                  OR LOWER(c.category_name) LIKE '%air conditioner%'
                  OR LOWER(c.category_name) LIKE '%ac%'
               ORDER BY p.product_name";
$aircon_result = $conn->query($aircon_sql);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];
    $quantity_input = (int)$_POST['quantity']; // Quantity of aircons sold
    $payment_method = $_POST['payment_method']; // 'cash' or 'installment'
    $selling_price = (float)$_POST['selling_price'];
    $cashier = $_POST['cashier_name'];
    $date_of_sale = date("Y-m-d H:i:s");
    
    // Calculate discount and final amounts
    $subtotal = $selling_price * $quantity_input;
    $discount_percentage = ($payment_method === 'cash') ? 10 : 0;
    $discount_amount = $subtotal * ($discount_percentage / 100);
    $total_amount = $subtotal - $discount_amount;

    // Step 1: Get current stock and product details from DB
    $stmt_fetch = $conn->prepare("SELECT product_name, quantity FROM products WHERE id = ?");
    $stmt_fetch->bind_param("i", $product_id);
    $stmt_fetch->execute();
    $stmt_fetch->bind_result($product_name, $current_stock);
    $stmt_fetch->fetch();
    $stmt_fetch->close();

    // Check stock availability
    if ($quantity_input > $current_stock) {
        $error_message = "Insufficient stock. Available: {$current_stock} units, Requested: {$quantity_input} units.";
    }

    if (empty($error_message)) {
        // Begin transaction
        $conn->begin_transaction();
        try {
            // Step 2: Insert into aircon_sales table
            $insert_sale = $conn->prepare("INSERT INTO aircon_sales (product_id, product_name, quantity_sold, unit_price, subtotal, payment_method, discount_percentage, discount_amount, total_amount, date_of_sale, cashier) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_sale->bind_param("isidisdddss", $product_id, $product_name, $quantity_input, $selling_price, $subtotal, $payment_method, $discount_percentage, $discount_amount, $total_amount, $date_of_sale, $cashier);
            $insert_sale->execute();

            // Step 3: Update inventory
            $new_stock = $current_stock - $quantity_input;

            // Ensure we don't have negative values
            $new_stock = max(0, $new_stock);

            // Update inventory
            $update_inventory = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $update_inventory->bind_param("ii", $new_stock, $product_id);
            $update_inventory->execute();

            $conn->commit();
            $discount_text = ($payment_method === 'cash') ? " with 10% cash discount (₱" . number_format($discount_amount, 2) . " saved)" : "";
            $success_message = "Sale recorded successfully! Sold: {$quantity_input} unit(s) of {$product_name}{$discount_text}. Total: ₱" . number_format($total_amount, 2);
            
            // Auto refresh after 3 seconds
            echo "<meta http-equiv='refresh' content='3'>";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Fetch recent sales for display
$sales_query = "SELECT * FROM aircon_sales ORDER BY sale_id DESC LIMIT 50";
$sales_result = $conn->query($sales_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sales Records - Aircon Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS (v4.6.2 - consistent with your theme) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- SB Admin 2 Custom styles -->
    <link href="https://cdn.jsdelivr.net/gh/StartBootstrap/startbootstrap-sb-admin-2/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <!-- Custom Employee CSS -->
    <link rel="stylesheet" href="../employee/css/employee.css">
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

                    <!-- Success/Error Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Success!</strong> <?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Error!</strong> <?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-snowflake mr-2"></i>Aircon Sales Records
                        </h1>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addSaleModal">
                            <i class="fas fa-plus mr-2"></i>New Sale
                        </button>
                    </div>

                    <!-- Sales Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <!-- <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-table mr-2"></i>Recent Sales Records
                            </h6> -->
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="salesTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Payment</th>
                                            <th>Discount</th>
                                            <th>Total</th>
                                            <th>Cashier</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($sales_result && $sales_result->num_rows > 0): ?>
                                            <?php while($row = $sales_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td>#<?php echo str_pad($row['sale_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                                    <td>
                                                        <i class="fas fa-snowflake text-info mr-1"></i>
                                                        <?php echo htmlspecialchars($row['product_name']); ?>
                                                    </td>
                                                    <td><span class="badge badge-info"><?php echo $row['quantity_sold']; ?></span></td>
                                                    <td>₱<?php echo number_format($row['unit_price'], 2); ?></td>
                                                    <td>
                                                        <?php if ($row['payment_method'] === 'cash'): ?>
                                                            <span class="badge badge-success"><i class="fas fa-money-bill-wave mr-1"></i>Cash</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning"><i class="fas fa-credit-card mr-1"></i>Installment</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['discount_percentage'] > 0): ?>
                                                            <span class="text-success">
                                                                <?php echo $row['discount_percentage']; ?>%
                                                                (-₱<?php echo number_format($row['discount_amount'], 2); ?>)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong>₱<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                                                    <td>
                                                        <i class="fas fa-user-circle text-primary mr-1"></i>
                                                        <?php echo htmlspecialchars($row['cashier']); ?>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y ', strtotime($row['date_of_sale'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewSale(<?php echo $row['sale_id']; ?>)" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" onclick="printReceipt(<?php echo $row['sale_id']; ?>)" title="Print Receipt">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">No sales records found.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->
<!-- Add Sale Modal -->
    <div class="modal fade" id="addSaleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="saleForm" method="post" action="">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-snowflake mr-2"></i>Record New Aircon Sale
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Aircon Model -->
                            <div class="col-md-12 mb-3">
                                <label for="product_id" class="form-label">
                                    <i class="fas fa-snowflake mr-1"></i>Aircon Model
                                </label>
                                <select class="form-control" name="product_id" id="product_id" required>
                                    <option value="">Select Aircon Model</option>
                                    <?php if ($aircon_result && $aircon_result->num_rows > 0): ?>
                                        <?php
                                        $aircon_result->data_seek(0);
                                        while($product = $aircon_result->fetch_assoc()):
                                            ?>
                                            <option value="<?php echo $product['id']; ?>"
                                                    data-price="<?php echo $product['selling_price']; ?>"
                                                    data-stock="<?php echo $product['quantity']; ?>"
                                                    data-name="<?php echo htmlspecialchars($product['product_name']); ?>">
                                                <?php echo htmlspecialchars($product['product_name']); ?>
                                                <?php if (!empty($product['capacity'])): ?>
                                                    (<?php echo htmlspecialchars($product['capacity']); ?>)
                                                <?php endif; ?>
                                                - Stock: <?php echo $product['quantity']; ?> units - ₱<?php echo number_format($product['selling_price'], 2); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>Shows available stock and selling price
                                </small>
                            </div>

                            <!-- Quantity -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-sort-numeric-up mr-1"></i> Quantity
                                </label>
                                <input type="number" class="form-control" name="quantity" id="quantity" min="1" step="1" placeholder="e.g., 2" required>
                                <small class="form-text" id="stockInfo">Select aircon model first</small>
                            </div>

                            <!-- Selling Price -->
                            <div class="col-md-6 mb-3">
                                <label for="selling_price" class="form-label">
                                    <i class="fas fa-peso-sign mr-1"></i>Unit Price
                                </label>
                                <input type="number" class="form-control" name="selling_price" id="selling_price" step="0.01" min="0" required>
                            </div>

                            <!-- Payment Method -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-credit-card mr-1"></i>Payment Method
                                </label>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="card border-success payment-option" data-method="cash">
                                            <div class="card-body text-center">
                                                <input type="radio" name="payment_method" value="cash" id="cash" required>
                                                <label for="cash" class="mb-0 d-block cursor-pointer">
                                                    <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                                    <h6 class="text-success">Cash Payment</h6>
                                                    <small class="text-success font-weight-bold">10% Discount</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card border-warning payment-option" data-method="installment">
                                            <div class="card-body text-center">
                                                <input type="radio" name="payment_method" value="installment" id="installment" required>
                                                <label for="installment" class="mb-0 d-block cursor-pointer">
                                                    <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                                                    <h6 class="text-warning">Installment</h6>
                                                    <small class="text-muted">Full Price</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cashier Name -->
                            <div class="col-md-12 mb-3">
                                <label for="cashier_name" class="form-label">
                                    <i class="fas fa-user mr-1"></i>Cashier Name
                                </label>
                                <input type="text" class="form-control" name="cashier_name" id="cashier_name"
                                    value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3" id="saleInfo" style="display: none;">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span id="saleDetails"></span>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="card bg-light mt-3" id="priceBreakdown">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Subtotal:</small>
                                        <div id="subtotalDisplay">₱0.00</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Discount:</small>
                                        <div id="discountDisplay" class="text-success">₱0.00 (0%)</div>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="text-center">
                                    <h5 class="mb-0" id="totalDisplay">Total: ₱0.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitSale">
                            <i class="fas fa-save mr-1"></i>Record Sale
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <!-- Bootstrap Bundle -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <!-- SB Admin 2 scripts -->
    <script src="https://cdn.jsdelivr.net/gh/StartBootstrap/startbootstrap-sb-admin-2/js/sb-admin-2.min.js"></script>

    <script>
         function calculateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const price = parseFloat(document.getElementById('selling_price').value) || 0;
            const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
            const productName = selectedOption.getAttribute('data-name') || '';
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            const subtotal = quantity * price;
            const isDiscounted = paymentMethod && paymentMethod.value === 'cash';
            const discountPercentage = isDiscounted ? 10 : 0;
            const discountAmount = subtotal * (discountPercentage / 100);
            const total = subtotal - discountAmount;
            
            // Update displays
            document.getElementById('subtotalDisplay').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('discountDisplay').textContent = `₱${discountAmount.toFixed(2)} (${discountPercentage}%)`;
            document.getElementById('discountDisplay').className = isDiscounted ? 'text-success font-weight-bold' : 'text-muted';
            document.getElementById('totalDisplay').textContent = `Total: ₱${total.toFixed(2)}`;
            
            // Show sale info
            if (quantity > 0 && productName) {
                const paymentText = paymentMethod ? ` (${paymentMethod.value === 'cash' ? 'Cash - 10% discount' : 'Installment'})` : '';
                document.getElementById('saleDetails').textContent = `Selling ${quantity} unit(s) of ${productName}${paymentText}`;
                document.getElementById('saleInfo').style.display = 'block';
            } else {
                document.getElementById('saleInfo').style.display = 'none';
            }
        }

        // Add event listeners for calculation
        document.getElementById('quantity').addEventListener('input', function() {
            updateStockInfo();
            calculateTotal();
        });
        document.getElementById('selling_price').addEventListener('input', calculateTotal);
        
        // Payment method change
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Update visual selection
                document.querySelectorAll('.payment-option').forEach(option => {
                    option.classList.remove('border-primary', 'bg-light');
                });
                
                const selectedCard = document.querySelector(`.payment-option[data-method="${this.value}"]`);
                selectedCard.classList.add('border-primary', 'bg-light');
                
                calculateTotal();
            });
        });

        // Function to update stock info
        function updateStockInfo() {
            const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
            const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
            const requestedQuantity = parseInt(document.getElementById('quantity').value) || 0;
            
            const stockInfo = document.getElementById('stockInfo');
            const quantityInput = document.getElementById('quantity');
            
            if (selectedOption.value) {
                stockInfo.innerHTML = `Available: <span class="text-success">${stock} units</span>`;
                quantityInput.setAttribute('max', stock);
                
                // Show warning if requested quantity exceeds stock
                if (requestedQuantity > stock) {
                    stockInfo.innerHTML = `Available: <span class="text-danger">${stock} units</span> - <span class="text-danger">Exceeds stock!</span>`;
                }
            }
        }

        // Set preset prices when product is selected
        document.getElementById('product_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
                
                document.getElementById('selling_price').value = price.toFixed(2);
                document.getElementById('quantity').setAttribute('max', stock);
                
                updateStockInfo();
                calculateTotal();
            } else {
                document.getElementById('selling_price').value = '';
                document.getElementById('stockInfo').innerHTML = 'Select aircon model first';
                document.getElementById('quantity').removeAttribute('max');
                document.getElementById('saleInfo').style.display = 'none';
                
                // Reset price breakdown
                document.getElementById('subtotalDisplay').textContent = '₱0.00';
                document.getElementById('discountDisplay').textContent = '₱0.00 (0%)';
                document.getElementById('totalDisplay').textContent = 'Total: ₱0.00';
            }
        });

        // Form submission with loading state and stock validation
        document.getElementById('saleForm').addEventListener('submit', function(e) {
            const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
            const requestedQuantity = parseInt(document.getElementById('quantity').value) || 0;
            const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
            
            // Validate stock
            if (requestedQuantity > stock) {
                e.preventDefault();
                alert(`Error: Requested ${requestedQuantity} units exceeds available stock (${stock} units).`);
                return false;
            }
            
            const submitBtn = document.getElementById('submitSale');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Recording...';
            submitBtn.disabled = true;
        });

        // Action functions
        function viewSale(id) {
            alert(`Viewing sale #${id}`);
        }

        function printReceipt(id) {
            alert(`Printing receipt for sale #${id}`);
        }

        // Initialize DataTable
        $(document).ready(function () {
            $('#salesTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthChange: false,
                order: [[0, 'desc']]
            });
        });
        
        // Style for cursor pointer
        $('<style>.cursor-pointer { cursor: pointer; }</style>').appendTo('head');
    </script>
</body>
</html>