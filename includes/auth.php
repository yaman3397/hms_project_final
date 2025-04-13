<?php
// Require DB connection
require_once(__DIR__ . '/../config/db.php');

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /hms/login.php");
    exit();
}

// Function to restrict access by role
function hasRole($requiredRole) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        echo "<script>alert('Access denied. Insufficient permissions.');window.location.href='/hms/logout.php';</script>";
        exit();
    }
}
?>
