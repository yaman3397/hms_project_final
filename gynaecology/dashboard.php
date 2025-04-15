<?php
require_once('../includes/auth.php');
hasRole('gynaecologist');
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

$totalPatients = getCount($conn, "SELECT COUNT(*) FROM gynae_patients");
$currentIPD = getCount($conn, "SELECT COUNT(*) FROM gynae_ipd_records WHERE discharge_date IS NULL");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gynaecology HMS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #ffeef3, #fff8fa);
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
      color: #692146;
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
      background: linear-gradient(to right, #fcd6e2, #fbeaf2);
      border-radius: 16px;
      padding: 1.5rem;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
    }

    .stats-card h6 {
      font-size: 0.9rem;
      color: #ba3e6a;
      margin-bottom: 0.5rem;
    }

    .stats-card h3 {
      font-size: 2rem;
      font-weight: 700;
      color: #692146;
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
      background: #e09cb8;
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
      background: #fce8f1;
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
        <thead><tr><th>MR No.</th><th>Name</th><th>OPD No.</th></tr></thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT mr_number, name, opd_no FROM gynae_patients ORDER BY opd_no DESC LIMIT 10");
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['mr_number']) ?></td>
              <td><?= htmlspecialchars($row['name']??'') ?></td>
              <td><?= htmlspecialchars($row['opd_no']??'') ?></td>
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
            SELECT r.mr_number, r.ipd_no, COALESCE(p.name, '') AS patient_name, r.admission_date
            FROM gynae_ipd_records r
            LEFT JOIN gynae_patients p ON r.mr_number = p.mr_number
            WHERE r.mr_number IS NOT NULL AND r.ipd_no IS NOT NULL
            ORDER BY r.id DESC LIMIT 10
          ");
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['mr_number']) ?></td>
              <td><?= htmlspecialchars($row['ipd_no']) ?></td>
              <td><?= htmlspecialchars($row['patient_name']??'') ?></td>
              <td><?= htmlspecialchars($row['admission_date']??'') ?></td>
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
            FROM gynae_invoices
            ORDER BY bill_no DESC LIMIT 10
          ");
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['bill_no']) ?></td>
              <td><?= htmlspecialchars($row['mr_number']) ?></td>
              <td><?= htmlspecialchars($row['patient_name']??'') ?></td>
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
