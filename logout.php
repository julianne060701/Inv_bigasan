<?php
session_start();

// Destroy all session data
session_destroy();

// Start a new session and set logout flag
session_start();
$_SESSION['logout'] = true;

// Redirect to login page with logout parameter
header("Location: login.php?logout=1");
exit();
?>