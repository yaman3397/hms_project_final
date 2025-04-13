<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$success = $error = "";

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if ($username && $password && $role) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, role, department) VALUES (?, ?, ?, 'children')");
        $stmt->bind_param("sss", $username, $hashed_password, $role);

        if ($stmt->execute()) {
            $success = "User added successfully.";
        } else {
            $error = "Error: Username may already exist.";
        }
    } else {
        $error = "All fields are required.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id = $delete_id AND department = 'children'");
    $success = "User deleted successfully.";
}

// Fetch users
$result = $conn->query("SELECT id, username, role FROM users WHERE department = 'children' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main { margin-left: 240px; padding: 2rem; }
    </style>
</head>
<body class="bg-light">
<div class="main">
    <h3>User Management - Children</h3>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add User Form -->
    <form method="POST" class="p-3 bg-white rounded shadow-sm mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="doctor">Doctor</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" name="add_user" class="btn btn-primary w-100">Add</button>
            </div>
        </div>
    </form>

    <!-- User List -->
    <div class="bg-white p-3 rounded shadow-sm">
        <h5>User List</h5>
        <table class="table table-bordered mt-3">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $count++ ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['role']) ?></td>
                        <td>
                            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($count === 1): ?>
                    <tr><td colspan="4" class="text-center">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
