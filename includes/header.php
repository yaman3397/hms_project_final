<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>HMS - Hospital Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <!-- Custom Styles -->
  <link rel="stylesheet" href="/assets/css/style.css">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      padding: 0;
    }

    .topbar {
      height: 60px;
      background-color: #ffffff;
      border-bottom: 1px solid #e0e0e0;
      padding: 0 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1000;
      margin-left: 250px;
    }

    .topbar h6 {
      margin: 0;
      font-weight: 600;
      font-size: 1rem;
      color: #333;
    }

    .topbar .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .main-content {
      margin-left: 250px;
      padding: 2rem;
    }
  </style>
</head>
<body>

  <!-- Topbar -->
  <div class="topbar">
    <h6>Hospital Management System</h6>
    <div class="user-info">
      <i class="bi bi-person-circle fs-4 text-primary"></i>
      <span class="fw-semibold">Welcome, <?= $_SESSION['username'] ?? 'User' ?></span>
    </div>
  </div>

  <!-- Main Content Wrapper -->
  <div class="main-content">
