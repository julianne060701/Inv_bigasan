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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../includes/header.php'); ?>
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
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Add your dashboard cards or widgets here -->
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

</body>

</html>