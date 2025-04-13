<?php
require_once('../includes/auth.php');
hasRole('gynaecologist');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gynaecology Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #fdf3f9;
      font-family: 'Segoe UI', sans-serif;
    }

    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: 240px;
      height: 100vh;
      background-color: #ffffff;
      border-right: 1px solid #e0e0e0;
      padding: 1.5rem;
    }

    .sidebar h4 {
      font-weight: 700;
      color: #ba68c8;
      margin-bottom: 2rem;
    }

    .sidebar a {
      display: block;
      color: #333;
      text-decoration: none;
      margin: 1rem 0;
      font-weight: 500;
    }

    .sidebar a:hover {
      color: #ba68c8;
    }

    .main {
      margin-left: 240px;
      padding: 2rem;
      background-color: #fff;
      min-height: 100vh;
    }

    .card-stat {
      border-radius: 15px;
      padding: 1.5rem;
      background: #f3e5f5;
      box-shadow: 0 4px 10px rgba(0,0,0,0.04);
      text-align: center;
    }

    .card-stat h6 {
      font-weight: 600;
    }

    .quick-actions a {
      margin: 0.5rem;
      font-weight: 500;
    }

    .recent-patients th {
      background: #f3e5f5;
    }

    .btn-glass {
      background: #ba68c8;
      color: white;
      border-radius: 10px;
      padding: 0.6rem 1.2rem;
      font-weight: 500;
    }

    .btn-glass:hover {
      background: #aa47bc;
    }

    .header-title {
      font-size: 1.8rem;
      font-weight: 600;
      color: #333;
    }

    .small-title {
      color: #999;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h4>Gynae HMS</h4>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="opd.php">🧾 OPD</a>
  <a href="ipd.php">🏥 IPD</a>
  <a href="discharge.php">📄 Discharge Summary</a>
  <a href="invoice.php">💳 Invoice</a>
  <a href="users.php">👤 Admin</a>
  <a href="../logout.php">🚪 Logout</a>
</div>

<div class="main">
  <div class="mb-4">
    <div class="header-title">Gynaecology Department Dashboard</div>
    <div class="small-title">Welcome, <?= $_SESSION['username'] ?> 👋</div>
  </div>

  <div class="row mb-4">
    <?php
    $stats = [
      ['label' => 'Total Patients', 'value' => '0'],
      ['label' => 'Today\'s OPD', 'value' => '0'],
      ['label' => 'Current IPD', 'value' => '0'],
      ['label' => 'Discharges', 'value' => '0']
    ];
    foreach ($stats as $s):
    ?>
      <div class="col-md-3">
        <div class="card-stat">
          <h6><?= $s['label'] ?></h6>
          <h3><?= $s['value'] ?></h3>
          <small class="text-muted">Updated today</small>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mb-4">
    <h5>Quick Actions</h5>
    <div class="quick-actions">
      <a href="opd.php" class="btn btn-glass">➕ New OPD Registration</a>
      <a href="ipd.php" class="btn btn-outline-info">🛏 New IPD Admission</a>
      <a href="discharge.php" class="btn btn-outline-secondary">📝 Discharge Summary</a>
      <a href="invoice.php" class="btn btn-outline-danger">💳 Generate Invoice</a>
    </div>
  </div>

  <div>
    <h5>Recent Patients</h5>
    <table class="table recent-patients">
      <thead>
        <tr>
          <th>MR No.</th>
          <th>Name</th>
          <th>Contact</th>
          <th>Visit Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr><td colspan="5" class="text-muted text-center">No recent patients found</td></tr>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
