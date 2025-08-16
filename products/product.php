<?php
session_start();
include '../config/conn.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory - Categories</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/rowgroup/1.4.0/css/rowGroup.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include '../includes/topbar.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0 text-gray-800">Product Inventory</h3>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>

                    <!-- Category Filter -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="categoryFilter" class="form-label">Filter by Category:</label>
                            <select id="categoryFilter" class="form-select">
                                <option value="">All Categories</option>
                                <?php
                                $categoryQuery = "SELECT DISTINCT c.category_name 
                                                FROM category c 
                                                INNER JOIN products p ON c.category_id = p.category_id 
                                                ORDER BY c.category_name";
                                $categoryResult = $conn->query($categoryQuery);
                                while ($categoryRow = $categoryResult->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($categoryRow['category_name']) . "'>" 
                                         . htmlspecialchars($categoryRow['category_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Capacity</th>
                                            <th>Selling Price</th>
                                            <th>Category</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $sql = "SELECT p.*, c.category_name 
                                            FROM products p 
                                            LEFT JOIN category c ON p.category_id = c.category_id 
                                            ORDER BY c.category_name, p.product_name";
                                    $result = $conn->query($sql);

                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['product_name']) . "</td>
                                                <td>" . htmlspecialchars($row['capacity']) . "</td>
                                                <td>₱" . number_format($row['selling_price'], 2) . "</td>
                                                <td>" . htmlspecialchars($row['category_name']) . "</td>
                                                <td>
                                                    <a href='edit_product.php?id=" . $row['id'] . "' class='btn btn-sm btn-warning'>
                                                        <i class='fas fa-edit'></i>
                                                    </a>
                                                    <a href='delete_product.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\");'>
                                                        <i class='fas fa-trash'></i>
                                                    </a>
                                                </td>
                                              </tr>";
                                    }
                                    ?>
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
            <?php include '../includes/footer.php'; ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->


    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form action="add_products.php" method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Product Name</label>
              <input type="text" name="product_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Capacity</label>
              <input type="text" name="capacity" class="form-control" placeholder="e.g. 1.5 or 4.0/3tr" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Buying Price</label>
              <input type="number" step="0.01" name="buying_price" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Selling Price (SRP)</label>
              <input type="number" step="0.01" name="selling_price" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select" required>
                <option value="">-- Select Category --</option>
                <?php
                $catRes = $conn->query("SELECT * FROM category ORDER BY category_name");
                while ($cat = $catRes->fetch_assoc()) {
                    echo "<option value='" . $cat['category_id'] . "'>" . htmlspecialchars($cat['category_name']) . "</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>


    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.4.0/js/dataTables.rowGroup.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function () {
        var table = $('#dataTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "searching": true,
            "responsive": true,
            "order": [[3, 'asc'], [0, 'asc']], // Sort by category first, then product name
            "columnDefs": [
                { "orderable": false, "targets": -1 }
            ],
            "rowGroup": {
                "dataSrc": 3, // Group by category column (index 3)
                "startRender": function (rows, group) {
                    return $('<tr class="table-primary group-header"><td colspan="5"><strong>' + group.toUpperCase() + '</strong></td></tr>');
                }
            }
        });

        // Category filter functionality
        $('#categoryFilter').on('change', function() {
            var selectedCategory = $(this).val();
            
            if (selectedCategory === '') {
                // Show all categories
                table.column(3).search('').draw();
            } else {
                // Filter by selected category
                table.column(3).search('^' + selectedCategory + '$', true, false).draw();
            }
        });

        // Optional: Add clear filter button functionality
        $(document).on('click', '.clear-filter', function() {
            $('#categoryFilter').val('');
            table.column(3).search('').draw();
        });
    });
    </script>

    <style>
    .group-header {
        font-weight: bold;
        background-color: #f8f9fc !important;
    }
    
    .group-header td {
        padding: 12px 8px;
    }
    </style>

</body>
</html>