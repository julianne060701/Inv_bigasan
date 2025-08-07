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
</head>

<body id="page-top">
<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('../includes/topbar.php'); ?>

            <div class="container-fluid">
                <h1 class="h3 mb-2 text-gray-800">Rice Categories</h1>

                <!-- Add Category Button -->
                <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#addCategoryModal">
                    <i class="fas fa-plus"></i> Add Category
                </button>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Category List</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type of Category</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    include '../config/conn.php';

                                    $query = "SELECT * FROM category ORDER BY category_id DESC";
                                    $result = $conn->query($query);

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $category_id = $row['category_id'];
                                            $modalId = "modal_" . $category_id;

                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['category_id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                            echo "<td class='text-center'>
                                                    <button class='btn btn-sm btn-info' data-toggle='modal' data-target='#$modalId' title='View'>
                                                        <i class='fas fa-eye'></i>
                                                    </button>
                                                    <a href='edit_category.php?id=$category_id' class='btn btn-sm btn-primary' title='Edit'>
                                                        <i class='fas fa-edit'></i>
                                                    </a>
                                                  </td>";
                                            echo "</tr>";

                                            // View Modal
                                            echo "
                                            <div class='modal fade' id='$modalId' tabindex='-1' role='dialog' aria-labelledby='modalLabel_$modalId' aria-hidden='true'>
                                                <div class='modal-dialog' role='document'>
                                                    <div class='modal-content'>
                                                        <div class='modal-header'>
                                                            <h5 class='modal-title' id='modalLabel_$modalId'>Category Details</h5>
                                                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                <span aria-hidden='true'>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class='modal-body'>
                                                            <p><strong>ID:</strong> " . htmlspecialchars($row['category_id']) . "</p>
                                                            <p><strong>Type of Category:</strong> " . htmlspecialchars($row['category_name']) . "</p>
                                                        </div>
                                                        <div class='modal-footer'>
                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center'>No category records found.</td></tr>";
                                    }

                                    $conn->close();
                                    ?>
                                </tbody>
                            </table>

                            <!-- Add Category Modal -->
                            <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <form action="add_category.php" method="POST">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="category_name">Category Name</label>
                                                    <input type="text" class="form-control" id="category_name" name="category_name" required>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">Add Category</button>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div> <!-- table-responsive -->
                    </div> <!-- card-body -->
                </div> <!-- card -->
            </div> <!-- container-fluid -->
        </div> <!-- content -->

        <?php include('../includes/footer.php'); ?>
    </div>
</div>

<!-- Scripts -->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function () {
        $('#dataTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "searching": true
        });
    });
</script>

</body>
</html>
