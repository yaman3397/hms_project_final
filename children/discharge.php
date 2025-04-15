<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$success = $error = "";
$data = [];

// Generate discharge ID
function getNextDischargeId($conn) {
    $year = date('Y');
    $prefix = "DIS-$year-";
    $res = $conn->query("SELECT MAX(discharge_id) as max_id FROM discharge_summary WHERE discharge_id LIKE '$prefix%'");
    $row = $res->fetch_assoc();
    if ($row && $row['max_id']) {
        $last = (int)str_replace($prefix, '', $row['max_id']);
        return $prefix . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    } else {
        return $prefix . "0001";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $mr_number = trim($_POST['mr_number']);
    $stmt = $conn->prepare("
        SELECT 
            p.mr_number, p.name AS patient_name, p.age, p.sex, p.mobile, p.department,
            p.doctor AS doctor_name, p.address,
            i.ipd_no, i.ward_bed AS ward, i.admission_date, i.admission_time,
            i.discharge_date, i.discharge_time, i.email, i.diagnosis
        FROM patients p
        JOIN ipd_records i ON p.mr_number = i.mr_number
        WHERE p.mr_number = ?
        ORDER BY i.id DESC LIMIT 1
    ");
    $stmt->bind_param("s", $mr_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
    } else {
        $error = "No record found for MR No: $mr_number";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_discharge'])) {
    $discharge_id = getNextDischargeId($conn);

    $admission_date = !empty($_POST['admission_date']) ? $_POST['admission_date'] : null;
    $admission_time = !empty($_POST['admission_time']) ? $_POST['admission_time'] : null;
    $discharge_date = !empty($_POST['discharge_date']) ? $_POST['discharge_date'] : null;
    $discharge_time = !empty($_POST['discharge_time']) ? $_POST['discharge_time'] : null;

    $stmt = $conn->prepare("INSERT INTO discharge_summary (
        discharge_id, mr_number, ipd_no, patient_name, age, sex, mobile, email,
        doctor_name, department, ward, room_no, admission_date, admission_time,
        discharge_date, discharge_time, discharge_type,
        diagnosis, reason, findings, `procedure`, treatment_given,
        discharge_condition, treatment_advised, second_opinion, urgent_care,
        follow_up, address
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssssssssssssssssssssssss",
        $discharge_id, $_POST['mr_number'], $_POST['ipd_no'], $_POST['patient_name'], $_POST['age'], $_POST['sex'],
        $_POST['mobile'], $_POST['email'], $_POST['doctor_name'], $_POST['department'], $_POST['ward'],
        $_POST['room_no'], $admission_date, $admission_time, $discharge_date,
        $discharge_time, $_POST['discharge_type'],
        $_POST['diagnosis'], $_POST['reason'], $_POST['findings'], $_POST['procedure'],
        $_POST['treatment_given'], $_POST['discharge_condition'], $_POST['treatment_advised'],
        $_POST['second_opinion'], $_POST['urgent_care'], $_POST['follow_up'], $_POST['address']
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Discharge Summary saved.";
        $_SESSION['discharge_id'] = $discharge_id;
        header("Location: discharge.php");
        exit;
    } else {
        $error = "Failed to save: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Discharge Summary - Children</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #f4f6fa, #fdfdff);
      min-height: 100vh;
    }
    .main {
      margin-left: 260px;
      padding: 3rem 2rem;
      background: linear-gradient(to bottom right, #f7f8ff, #ebeefe);
    }
    .glass-card {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(14px);
      border-radius: 24px;
      padding: 2rem;
      max-width: 1080px;
      margin: auto;
      border: 1px solid rgba(255, 255, 255, 0.5);
      box-shadow: 0 8px 16px rgba(0,0,0,0.05);
    }
    h2 {
      color: #352f78;
      font-weight: 700;
      margin-bottom: 1.5rem;
    }
    label {
      color: #1f1f3c;
      font-weight: 600;
    }
    .form-control {
      border-radius: 14px;
      border: 1px solid #ddd;
    }
    .form-control:focus {
      border-color: #8d79f6;
      box-shadow: 0 0 0 3px rgba(141, 121, 246, 0.1);
    }
    .btn-primary {
      background: linear-gradient(to right, #8d79f6, #7464ea);
      border: none;
      border-radius: 30px;
      font-weight: 600;
    }
    .alert {
      border-radius: 12px;
      font-weight: 500;
    }
  </style>
</head>
<body>
<div class="main">
  <div class="glass-card">
    <h2>Discharge Summary</h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Search MR No -->
    <form method="POST" class="row g-3 mb-4">
      <div class="col-md-6">
        <input type="text" name="mr_number" class="form-control" placeholder="Enter MR Number" required>
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary" name="search">Search</button>
      </div>
    </form>

    <?php if (!empty($data)): ?>
    <form method="POST" class="row g-3">
      <input type="hidden" name="save_discharge" value="1">

      <?php
      $fields = [
        ['mr_number', 'MR Number'], ['ipd_no', 'IPD No'], ['patient_name', 'Patient Name'], ['age', 'Age'],
        ['sex', 'Sex'], ['mobile', 'Mobile'], ['email', 'Email'], ['doctor_name', 'Doctor'],
        ['department', 'Department'], ['ward', 'Ward'], ['room_no', 'Room No'], ['admission_date', 'Admission Date'],
        ['admission_time', 'Admission Time'], ['discharge_date', 'Discharge Date'], ['discharge_time', 'Discharge Time'], ['address', 'Address']
      ];
      foreach ($fields as [$key, $label]) {
          $val = $data[$key] ?? '';
          echo "<div class='col-md-4'><label>$label</label><input type='text' class='form-control' name='$key' value='".htmlspecialchars($val)."'></div>";
      }
      ?>

      <div class="col-md-4">
        <label>Discharge Type</label>
        <input type="text" name="discharge_type" class="form-control">
      </div>

      <?php
      $textareas = [
        'diagnosis' => 'Diagnosis',
        'reason' => 'Reason for Admission',
        'findings' => 'Significant Findings',
        'procedure' => 'Procedure / Operation',
        'treatment_given' => 'Treatment Given',
        'discharge_condition' => 'Condition at Discharge',
        'treatment_advised' => 'Treatment Advised',
        'second_opinion' => 'Second Opinion',
        'urgent_care' => 'Urgent Care',
        'follow_up' => 'Follow-up Advice'
      ];
      foreach ($textareas as $name => $label) {
          echo "<div class='col-md-12'><label>$label</label><textarea name='$name' class='form-control'></textarea></div>";
      }
      ?>

      <div class="col-12 text-end mt-3">
        <button type="submit" class="btn btn-primary">Save & Print</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="dischargeModal" tabindex="-1" aria-labelledby="dischargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dischargeModalLabel">Success</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Discharge Summary saved successfully.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="#" target="_blank" id="printLink" class="btn btn-primary">Print Summary</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  <?php if (!empty($_SESSION['discharge_id'])): ?>
    window.onload = function () {
      const modal = new bootstrap.Modal(document.getElementById('dischargeModal'));
      document.getElementById('printLink').href = 'discharge_print.php?print_id=<?= $_SESSION['discharge_id'] ?>';
      modal.show();
    };
    <?php unset($_SESSION['discharge_id']); ?>
  <?php endif; ?>
</script>
<?php include('../includes/footer.php'); ?>
</body>
</html>