<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Inventory - Products</title>

    <!-- Font Awesome -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('../includes/topbar.php'); ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Products</h1>

                    <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#addProductModal">
                        <i class="fas fa-plus"></i> Add Product
                    </button>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Product List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Buying Price</th>
                                            <th>Selling Price</th>
                                            <th>Quantity</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        include '../config/conn.php';

                                        $query = "
                                            SELECT p.*, c.category_name 
                                            FROM products p 
                                            LEFT JOIN category c ON p.category_id = c.category_id 
                                            ORDER BY p.id DESC";

                                        $result = $conn->query($query);

                                        if ($result && $result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $productId = htmlspecialchars($row['id']);
                                                $modalId = "modal_" . $productId;

                                                echo "<tr>";
                                                echo "<td>" . $productId . "</td>";
                                                echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['category_name'] ?? 'N/A') . "</td>";
                                                echo "<td>₱" . number_format($row['buying_price'], 2) . "</td>";
                                                echo "<td>₱" . number_format($row['selling_price'], 2) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                                                echo "<td class='text-center'>";
                                                echo "<button class='btn btn-sm btn-info me-1' data-toggle='modal' data-target='#$modalId' title='View'>";
                                                echo "<i class='fas fa-eye'></i>";
                                                echo "</button>";
                                                echo "<a href='edit_product.php?id=$productId' class='btn btn-sm btn-primary' title='Edit'>";
                                                echo "<i class='fas fa-edit'></i>";
                                                echo "</a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7' class='text-center'>No product records found.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Product Details Modals -->
                    <?php
                    // Reset result pointer to beginning
                    if ($result && $result->num_rows > 0) {
                        $result->data_seek(0); // Reset to beginning
                        while ($row = $result->fetch_assoc()) {
                            $productId = htmlspecialchars($row['id']);
                            $modalId = "modal_" . $productId;
                            ?>
                            <div class='modal fade' id='<?php echo $modalId; ?>' tabindex='-1' role='dialog' aria-labelledby='modalLabel_<?php echo $modalId; ?>' aria-hidden='true'>
                                <div class='modal-dialog' role='document'>
                                    <div class='modal-content'>
                                        <div class='modal-header'>
                                            <h5 class='modal-title' id='modalLabel_<?php echo $modalId; ?>'>Product Details</h5>
                                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                <span aria-hidden='true'>&times;</span>
                                            </button>
                                        </div>
                                        <div class='modal-body'>
                                            <p><strong>Product Name:</strong> <?php echo htmlspecialchars($row['product_name']); ?></p>
                                            <p><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></p>
                                            <p><strong>Buying Price:</strong> ₱<?php echo number_format($row['buying_price'], 2); ?></p>
                                            <p><strong>Selling Price:</strong> ₱<?php echo number_format($row['selling_price'], 2); ?></p>
                                            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($row['quantity']); ?></p>
                                        </div>
                                        <div class='modal-footer'>
                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>

                    <!-- Add Product Modal -->
                    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <form action="add_products.php" method="POST">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="product_name">Product Name</label>
                                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="buying_price">Buying Price (₱)</label>
                                            <input type="number" step="0.01" class="form-control" id="buying_price" name="buying_price" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="selling_price">Selling Price (₱)</label>
                                            <input type="number" step="0.01" class="form-control" id="selling_price" name="selling_price" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="quantity">Quantity</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="category">Category</label>
                                            <select class="form-control" id="category" name="category_id" required>
                                                <option value="">Select Category</option>
                                                <?php
                                                $cat_query = "SELECT * FROM category ORDER BY category_name ASC";
                                                $cat_result = mysqli_query($conn, $cat_query);

                                                if ($cat_result && mysqli_num_rows($cat_result) > 0) {
                                                    while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                                        echo '<option value="' . htmlspecialchars($cat_row['category_id']) . '">' . htmlspecialchars($cat_row['category_name']) . '</option>';
                                                    }
                                                } else {
                                                    echo '<option value="" disabled>No categories found</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Add Product</button>
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div> <!-- End Add Product Modal -->

                </div>
            </div>

            <?php include('../includes/footer.php'); ?>
        </div>
    </div>

    <!-- JavaScript Includes -->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <!-- DataTable Init -->
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": -1 } // Disable ordering on last column (Actions)
                ]
            });
        });
    </script>

</body>
</html>