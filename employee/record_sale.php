<?php
include '../config/conn.php';
session_start(); // Needed for $_SESSION['username']

$success_message = '';
$error_message = '';

// Fetch from rice_inventory table for dropdown
$rice_sql = "SELECT rice_type, price_per_kg, sack_weight_kg, quantity_sacks, quantity_kg FROM rice_inventory";
$rice_result = $conn->query($rice_sql);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rice_type = $_POST['rice_type'];
    $quantity_input = (float)$_POST['quantity_sacks']; // This is the input quantity
    $unit = $_POST['unit']; // 'kg' or 'sack'
    $price_per_kg = (float)$_POST['price_per_kg'];
    $cashier = $_POST['cashier_name'];
    $date_of_sale = date("Y-m-d H:i:s");

    // Step 1: Get sack weight and current stock from DB
    $stmt_fetch = $conn->prepare("SELECT sack_weight_kg, quantity_sacks, quantity_kg FROM rice_inventory WHERE rice_type = ?");
    $stmt_fetch->bind_param("s", $rice_type);
    $stmt_fetch->execute();
    $stmt_fetch->bind_result($sack_weight_kg, $current_sacks, $current_kg);
    $stmt_fetch->fetch();
    $stmt_fetch->close();

    // Calculate quantities based on unit
    if ($unit === 'sack') {
        $quantity_sacks_sold = $quantity_input; // Number of sacks being sold
        $quantity_kg_sold = $quantity_input * $sack_weight_kg; // Convert sacks to kg
    } else {
        $quantity_sacks_sold = 0; // No full sacks sold, just loose kg
        $quantity_kg_sold = $quantity_input; // Direct kg amount
    }

    // Check stock availability
    $total_kg_available = $current_kg; // Assuming quantity_kg is total available kg
    
    if ($unit === 'sack') {
        // For sack sales, check if we have enough full sacks
        if ($quantity_sacks_sold > $current_sacks) {
            $error_message = "Insufficient sack stock. Available: {$current_sacks} sacks, Requested: {$quantity_sacks_sold} sacks.";
        }
    } else {
        // For kg sales, check total kg availability
        if ($quantity_kg_sold > $total_kg_available) {
            $error_message = "Insufficient stock. Available: {$total_kg_available} kg, Requested: {$quantity_kg_sold} kg.";
        }
    }

    if (empty($error_message)) {
        // Begin transaction
        $conn->begin_transaction();
        try {
            // Step 2: Insert into sales table
            $total_amount = $price_per_kg * $quantity_kg_sold;

            $insert_sale = $conn->prepare("INSERT INTO sales (rice_type, quantity_sold, unit, price_per_kg, total_amount, date_of_sale, cashier) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_sale->bind_param("sdssdss", $rice_type, $quantity_input, $unit, $price_per_kg, $total_amount, $date_of_sale, $cashier);
            $insert_sale->execute();

            // Step 3: Update inventory based on unit type - ENHANCED VERSION
            if ($unit === 'sack') {
                // Selling full sacks - subtract from both sacks count and total kg
                $new_sacks = $current_sacks - $quantity_sacks_sold;
                $new_kg = $current_kg - $quantity_kg_sold;
                
            } else {
                // Selling by kg - reduce total kg and recalculate equivalent sacks
                $new_kg = $current_kg - $quantity_kg_sold;
                
                // Calculate how many equivalent sacks remain based on remaining kg
                $new_sacks = $new_kg / $sack_weight_kg;
                
                // Note: $new_sacks can be fractional (e.g., 1.5 sacks)
                // If you want to store only whole sacks, use: floor($new_sacks)
            }

            // Ensure we don't have negative values
            $new_sacks = max(0, $new_sacks);
            $new_kg = max(0, $new_kg);

            // Update inventory
            $update_inventory = $conn->prepare("UPDATE rice_inventory SET quantity_sacks = ?, quantity_kg = ? WHERE rice_type = ?");
            $update_inventory->bind_param("ids", $new_sacks, $new_kg, $rice_type);
            $update_inventory->execute();

            $conn->commit();
            $success_message = "Sale recorded successfully! Sold: {$quantity_input} {$unit} of {$rice_type}";
            
            // Auto refresh after 2 seconds
            echo "<meta http-equiv='refresh' content='2'>";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Fetch recent sales for display
$sales_query = "SELECT * FROM sales ORDER BY sale_id DESC LIMIT 50";
$sales_result = $conn->query($sales_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('includes/header.php'); ?>
    <link rel="stylesheet" href="css/employee.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include('includes/sidebar.php'); ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include('includes/topbar.php'); ?>
                <!-- End of Topbar -->
                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Success/Error Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Success!</strong> <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Error!</strong> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-cash-register me-2"></i>Sales Records
                        </h1>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                            <i class="fas fa-plus me-2"></i>New Sale
                        </button>
                    </div>

                    <!-- Sales Summary -->
                    </div>
                    </div>

                    <!-- Sales Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5>
                                <i class="fas fa-table"></i>
                                Recent Sales Records
                            </h5>
                        </div>
                        <div class="card-body">

                            <div class="table-responsive">
                            <table class="table table-hover" id="salesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Rice Type</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Price/kg</th>
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
                                            <td><span>#<?php echo str_pad($row['sale_id'], 3, '0', STR_PAD_LEFT); ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-seedling text-success me-2"></i>
                                                    <?php echo htmlspecialchars($row['rice_type']); ?>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-info"><?php echo $row['quantity_sold']; ?></span></td>
                                            <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                            <td>₱<?php echo number_format($row['price_per_kg'], 2); ?></td>
                                            <td><strong>₱<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                                    <?php echo htmlspecialchars($row['cashier']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($row['date_of_sale'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewSale(<?php echo $row['sale_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" onclick="printReceipt(<?php echo $row['sale_id']; ?>)">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No sales records found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                            </div>
                            <div class="loading-spinner">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading sales data...</p>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('includes/footer.php'); ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

 <!-- Add Sale Modal -->
 <div class="modal fade" id="addSaleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="saleForm" method="post" action="">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-cash-register me-2"></i>Record New Sale
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                    <!-- Rice Type -->
                    <div class="col-md-6">
    <label for="rice_type" class="form-label">
        <i class="fas fa-box me-1"></i>Rice Type
    </label>
    <select class="form-select" name="rice_type" id="rice_type" required>
        <option value="">Select Rice Type</option>
        <?php if ($rice_result && $rice_result->num_rows > 0): ?>
            <?php
            $rice_result->data_seek(0);
            while($rice = $rice_result->fetch_assoc()):
                $total_available_kg = $rice['quantity_kg'];
                ?>
                <option value="<?php echo htmlspecialchars($rice['rice_type']); ?>"
                        data-price="<?php echo $rice['price_per_kg']; ?>"
                        data-stock-sacks="<?php echo $rice['quantity_sacks']; ?>"
                        data-stock-kg="<?php echo $rice['quantity_kg']; ?>"
                        data-total-kg="<?php echo $total_available_kg; ?>"
                        data-sack-weight="<?php echo $rice['sack_weight_kg']; ?>">
                    <?php echo htmlspecialchars($rice['rice_type']); ?>
                    (<?php echo $rice['quantity_sacks']; ?> sacks, <?php echo number_format($rice['quantity_kg'], 1); ?> kg total) - ₱<?php echo number_format($rice['price_per_kg'], 2); ?>/kg
                </option>
            <?php endwhile; ?>
        <?php endif; ?>
    </select>
    <small class="form-text text-muted">
        <i class="fas fa-info-circle me-1"></i>Shows available sacks, total kg and selling price
    </small>
</div>


                    <!-- Quantity + Unit -->
                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-sort-numeric-up"></i> Quantity
                    </label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="quantity_sacks" id="quantity_sacks" min="0.1" step="0.1" placeholder="e.g., 1.5" required>
                        <span class="input-group-text">KG / Sack</span>
                        <select class="form-select" name="unit" id="unit" required>
                            <option value="sack">Sack</option>
                            <option value="kg">Kilogram</option>
                        </select>
                    </div>
                    <small class="form-text" id="stockInfo">Select rice type first</small>
                </div>

                        <!-- Price per KG -->
                        <div class="col-md-6">
                            <label for="price_per_kg" class="form-label">
                                <i class="fas fa-peso-sign"></i>Price per KG
                            </label>
                            <input type="number" class="form-control" name="price_per_kg" id="price_per_kg" step="0.01" min="0" required>
                        </div>

                        <!-- Cashier Name -->
                        <div class="col-md-6">
                            <label for="cashier_name" class="form-label">
                                <i class="fas fa-user"></i>Cashier Name
                            </label>
                            <input type="text" class="form-control" name="cashier_name" id="cashier_name"
                                value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>" required>
                        </div>

                    </div>


                    <div class="alert alert-info mt-3" id="saleInfo" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="saleDetails"></span>
                    </div>

                    <div class="total-display mt-4" id="totalDisplay">
                        Total: ₱0.00
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitSale">
                        <i class="fas fa-save me-1"></i>Record Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


    <!-- Success Alert Template (hidden by default) -->
    <!-- <div class="alert alert-success alert-dismissible fade" role="alert" id="successAlert" style="display: none;">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Success!</strong> Sale recorded successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div> -->

<!-- jQuery (optional, if needed) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

<!-- Bootstrap Bundle (includes Popper.js) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


    <!-- SB Admin 2 scripts (optional) -->
<script src="https://cdn.jsdelivr.net/gh/StartBootstrap/startbootstrap-sb-admin-2/js/sb-admin-2.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- Your custom JS file -->
<script>
    
     // Calculate total automatically and show sale info
     function calculateTotal() {
         const quantity = parseFloat(document.getElementById('quantity_sacks').value) || 0;
         const price = parseFloat(document.getElementById('price_per_kg').value) || 0;
         const unit = document.getElementById('unit').value;
         const selectedOption = document.getElementById('rice_type').options[document.getElementById('rice_type').selectedIndex];
         const sackWeight = parseFloat(selectedOption.getAttribute('data-sack-weight')) || 0;
         
         let totalKg = 0;
         let saleInfoText = '';
         
         if (unit === 'sack') {
             totalKg = quantity * sackWeight;
             saleInfoText = `Selling ${quantity} sack(s) = ${totalKg.toFixed(1)} kg`;
         } else {
             totalKg = quantity;
             saleInfoText = `Selling ${quantity} kg`;
         }
         
         const total = totalKg * price;
         document.getElementById('totalDisplay').textContent = `Total: ₱${total.toFixed(2)}`;
         
         // Show sale info
         if (quantity > 0 && sackWeight > 0) {
             document.getElementById('saleDetails').textContent = saleInfoText;
             document.getElementById('saleInfo').style.display = 'block';
         } else {
             document.getElementById('saleInfo').style.display = 'none';
         }
     }

     // Add event listeners for calculation
     document.getElementById('quantity_sacks').addEventListener('input', calculateTotal);
     document.getElementById('price_per_kg').addEventListener('input', calculateTotal);
     document.getElementById('unit').addEventListener('change', function() {
         updateStockInfo();
         calculateTotal();
     });

     // Function to update stock info based on selected unit
     function updateStockInfo() {
         const selectedOption = document.getElementById('rice_type').options[document.getElementById('rice_type').selectedIndex];
         const unit = document.getElementById('unit').value;
         const stockSacks = parseInt(selectedOption.getAttribute('data-stock-sacks')) || 0;
         const stockKg = parseFloat(selectedOption.getAttribute('data-stock-kg')) || 0;
         const totalKg = parseFloat(selectedOption.getAttribute('data-total-kg')) || 0;
         
         const stockInfo = document.getElementById('stockInfo');
         const unitInfo = document.getElementById('unitInfo');
         const quantityInput = document.getElementById('quantity_sacks');
         
         if (unit === 'sack') {
             stockInfo.innerHTML = `Available: <span class="text-success">${stockSacks} sacks</span>`;
             unitInfo.innerHTML = 'Selling by full sacks';
             quantityInput.setAttribute('max', stockSacks);
             quantityInput.setAttribute('step', '1');
             quantityInput.setAttribute('min', '1');
         } else {
             stockInfo.innerHTML = `Available: <span class="text-success">${totalKg.toFixed(1)} kg total</span>`;
             unitInfo.innerHTML = 'Selling by weight (kg)';
             quantityInput.setAttribute('max', totalKg);
             quantityInput.setAttribute('step', '0.1');
             quantityInput.setAttribute('min', '0.1');
         }
     }

    // Set preset prices when product is selected
    document.getElementById('rice_type').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            // Get data from the selected option
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const stockSacks = parseInt(selectedOption.getAttribute('data-stock-sacks')) || 0;
            const stockKg = parseFloat(selectedOption.getAttribute('data-stock-kg')) || 0;
            const totalKg = parseFloat(selectedOption.getAttribute('data-total-kg')) || 0;
            
            // Update price field
            document.getElementById('price_per_kg').value = price.toFixed(2);
            
            // Update stock info based on current unit selection
            updateStockInfo();
            
            // Calculate total
            calculateTotal();
        } else {
            // Reset fields if no product selected
            document.getElementById('price_per_kg').value = '';
            document.getElementById('stockInfo').innerHTML = 'Select rice type first';
            document.getElementById('unitInfo').innerHTML = 'Choose selling unit';
            document.getElementById('quantity_sacks').removeAttribute('max');
            document.getElementById('saleInfo').style.display = 'none';
        }
    });

     // Form submission with loading state and stock validation
     document.getElementById('saleForm').addEventListener('submit', function(e) {
         const selectedOption = document.getElementById('rice_type').options[document.getElementById('rice_type').selectedIndex];
         const requestedQuantity = parseFloat(document.getElementById('quantity_sacks').value) || 0;
         const unit = document.getElementById('unit').value;
         const stockSacks = parseInt(selectedOption.getAttribute('data-stock-sacks')) || 0;
         const totalKg = parseFloat(selectedOption.getAttribute('data-total-kg')) || 0;
         
         // Validate based on unit type
         let validationError = '';
         if (unit === 'sack') {
             if (requestedQuantity > stockSacks) {
                 validationError = `Error: Requested ${requestedQuantity} sacks exceeds available stock (${stockSacks} sacks).`;
             }
         } else {
             if (requestedQuantity > totalKg) {
                 validationError = `Error: Requested ${requestedQuantity} kg exceeds available stock (${totalKg.toFixed(1)} kg).`;
             }
         }
         
         if (validationError) {
             e.preventDefault();
             alert(validationError);
             return false;
         }
         
         const submitBtn = document.getElementById('submitSale');
         submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Recording...';
         submitBtn.disabled = true;
     });

     // Search functionality
     document.getElementById('salesSearch').addEventListener('keyup', function() {
         const searchValue = this.value.toLowerCase();
         const rows = document.querySelectorAll('#salesTable tbody tr');
         
         rows.forEach(row => {
             const text = row.textContent.toLowerCase();
             row.style.display = text.includes(searchValue) ? '' : 'none';
         });
     });

     // Action functions
     function viewSale(id) {
         alert(`Viewing sale #${id}`);
         // Add your view logic here
     }

     function printReceipt(id) {
         alert(`Printing receipt for sale #${id}`);
         // Add your print logic here
     }

     // Show success message (call this after successful form submission)
     function showSuccessMessage() {
         const alert = document.getElementById('successAlert');
         alert.style.display = 'block';
         alert.classList.add('show');
         
         // Hide after 5 seconds
         setTimeout(() => {
             alert.classList.remove('show');
             setTimeout(() => {
                 alert.style.display = 'none';
             }, 150);
         }, 5000);
     }

</script>
<script>
    $(document).ready(function () {
        $('#salesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[0, 'desc']] // Sort by ID descending
        });
    });
</script>

</body>

</html>