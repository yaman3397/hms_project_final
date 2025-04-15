<?php
require_once('../includes/auth.php');
hasRole('children');
require_once('../config/db.php');
include('../includes/sidebar.php');

function getCount($conn, $query) {
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $stmt->bind_result($count);
  $stmt->fetch();
  $stmt->close();
  return $count;
}

$totalPatients = getCount($conn, "SELECT COUNT(*) FROM patients");
$currentIPD = getCount($conn, "SELECT COUNT(*) FROM ipd_records WHERE discharge_date IS NULL");
$totalNewborns = getCount($conn, "SELECT COUNT(*) FROM newborns");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Children HMS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #e7f0fd, #f8faff);
      font-family: 'Segoe UI', sans-serif;
    }

    .dashboard-container {
      margin-left: 260px;
      padding: 2rem;
    }

    .section-card {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(16px);
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07);
      margin-bottom: 2rem;
      border: 1px solid rgba(255, 255, 255, 0.6);
    }

    .section-card h5 {
      font-weight: 700;
      color: #2d2651;
      margin-bottom: 1.2rem;
    }

    .stats-box {
      display: flex;
      gap: 1.5rem;
      flex-wrap: wrap;
    }

    .stats-card {
      flex: 1;
      min-width: 220px;
      background: linear-gradient(to right, #d6cbfa, #e9e3fc);
      border-radius: 16px;
      padding: 1.5rem;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
    }

    .stats-card h6 {
      font-size: 0.9rem;
      color: #5e4e9e;
      margin-bottom: 0.5rem;
    }

    .stats-card h3 {
      font-size: 2rem;
      font-weight: 700;
      color: #2b235c;
    }

    .btn-sm-custom {
      font-size: 0.8rem;
      padding: 0.45rem 1.2rem;
      border-radius: 12px;
      font-weight: 600;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06);
    }

    .table-wrapper {
      max-height: 260px;
      overflow-y: auto;
    }

    table {
      border-radius: 8px;
      overflow: hidden;
    }

    table thead {
      background: #bbb2f5;
    }

    table thead th {
      color: white;
      font-size: 0.87rem;
    }

    table tbody td {
      font-size: 0.9rem;
      color: #333;
    }

    table tbody tr:hover {
      background: #efecfd;
    }
  </style>
</head>
<body>
<div class="dashboard-container">

  <!-- Hospital Overview -->
  <div class="section-card">
    <h5>Hospital Overview</h5>
    <div class="stats-box">
      <div class="stats-card">
        <h6>Total OPD Patients</h6>
        <h3><?= $totalPatients ?></h3>
      </div>
      <div class="stats-card">
        <h6>Current IPD</h6>
        <h3><?= $currentIPD ?></h3>
      </div>
    </div>
  </div>

  <!-- Recent OPD Patients -->
  <div class="section-card">
    <div class="d-flex justify-content-between align-items-center">
      <h5>Recent OPD Patients</h5>
      <a href="opd.php" class="btn btn-primary btn-sm-custom">Register OPD</a>
    </div>
    <div class="table-wrapper mt-3">
      <table class="table table-sm">
        <thead><tr><th>MR No.</th><th>Name</th><th>Date</th></tr></thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT mr_number, name, opd_no FROM patients ORDER BY mr_number DESC LIMIT 10");
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['mr_number']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['opd_no']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent IPD -->
  <div class="section-card">
    <div class="d-flex justify-content-between align-items-center">
      <h5>Recent IPD Admissions</h5>
      <a href="ipd.php" class="btn btn-info btn-sm-custom text-white">Admit IPD</a>
    </div>
    <div class="table-wrapper mt-3">
      <table class="table table-sm">
        <thead><tr><th>MR No.</th><th>IPD No.</th><th>Name</th><th>Date</th></tr></thead>
        <tbody>
          <?php
          $result = $conn->query("
            SELECT r.mr_number, r.ipd_no, p.name AS patient_name, r.admission_date
            FROM ipd_records r
            JOIN patients p ON r.mr_number = p.mr_number
            ORDER BY r.id DESC LIMIT 10
          ");
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['mr_number']) ?></td>
              <td><?= htmlspecialchars($row['ipd_no']) ?></td>
              <td><?= htmlspecialchars($row['patient_name']) ?></td>
              <td><?= htmlspecialchars($row['admission_date']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Newborn Registrations -->
  <div class="section-card">
    <div class="d-flex justify-content-between align-items-center">
      <h5>Newborn Registrations (Total: <?= $totalNewborns ?>)</h5>
      <a href="newborn.php" class="btn btn-success btn-sm-custom">Register Newborn</a>
    </div>
    <div class="table-wrapper mt-3">
      <table class="table table-sm">
        <thead><tr><th>MR No.</th><th>Name</th><th>Date of Birth</th></tr></thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT baby_mr, baby_name, dob FROM newborns ORDER BY id DESC LIMIT 10");
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['baby_mr']) ?></td>
              <td><?= htmlspecialchars($row['baby_name']) ?></td>
              <td><?= htmlspecialchars($row['dob']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Invoices -->
  <div class="section-card">
    <div class="d-flex justify-content-between align-items-center">
      <h5>Recent Invoices</h5>
      <a href="invoice.php" class="btn btn-warning btn-sm-custom">Create Invoice</a>
    </div>
    <div class="table-wrapper mt-3">
      <table class="table table-sm">
        <thead><tr><th>Invoice #</th><th>MR No.</th><th>Name</th><th>Amount</th></tr></thead>
        <tbody>
          <?php
          $result = $conn->query("
            SELECT bill_no, mr_number, patient_name, final_amount
            FROM invoices
            ORDER BY bill_no DESC LIMIT 10
          ");
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['bill_no']) ?></td>
              <td><?= htmlspecialchars($row['mr_number']) ?></td>
              <td><?= htmlspecialchars($row['patient_name']) ?></td>
              <td>₹<?= number_format($row['final_amount'], 2) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?php include('../includes/footer.php'); ?>
</body>
</html>
