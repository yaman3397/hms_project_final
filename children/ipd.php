<?php ob_start(); ?>
<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$success = $error = "";
$patient = null;

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (!isset($_SESSION['last_ipd_no'])) {
    $_SESSION['last_ipd_no'] = generateIPDNumber($conn);
}
$ipd_no = $_SESSION['last_ipd_no'];

function generateIPDNumber($conn) {
    $year = date('Y');
    $prefix = "IPD-$year-";

    $stmt = $conn->query("SELECT MAX(id) as max_id FROM ipd_records");
    $result = $stmt->fetch_assoc();
    $next = str_pad(($result['max_id'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);

    return $prefix . $next;
}

if (isset($_GET['mr_number'])) {
    $mr_number = $_GET['mr_number'];
    $stmt = $conn->prepare("SELECT * FROM patients WHERE mr_number = ?");
    $stmt->bind_param("s", $mr_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if (!$patient) $error = "Patient not found.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mr = $_POST['mr_number'];
    $ipd = $_POST['ipd_no'];

    if (empty($mr) || empty($ipd)) {
        $error = "MR Number and IPD Number are required.";
    } else {
        $ward = $_POST['ward'] ?? null;
        $bed = $_POST['bed'] ?? null;
        $ward_bed = ($ward && $bed) ? "$ward - $bed" : null;
        $doctor = $_POST['doctor'] ?? null;

        // Handle nullable dates and times
        $doa = !empty($_POST['doa']) ? $_POST['doa'] : null;
        $toa = !empty($_POST['toa']) ? $_POST['toa'] : null;
        $dod = !empty($_POST['dod']) ? $_POST['dod'] : null;
        $tod = !empty($_POST['tod']) ? $_POST['tod'] : null;

        $email = $_POST['email'] ?? null;
        $ref_by = $_POST['ref_by'] ?? null;
        $amount = !empty($_POST['amount']) ? floatval($_POST['amount']) : null;

        $stmt = $conn->prepare("INSERT INTO ipd_records (
            mr_number, ipd_no, ward_bed, doctor_name, admission_date,
            admission_time, discharge_date, discharge_time, email,
            ref_by, amount, diagnosis
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '')");

        // Updated types: 7 strings, 1 double (for amount)
        $stmt->bind_param(
            "ssssssssssd",
            $mr, $ipd, $ward_bed, $doctor, $doa,
            $toa, $dod, $tod, $email, $ref_by, $amount
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "IPD record saved successfully.";
            $_SESSION['last_ipd_no'] = generateIPDNumber($conn);
            header("Location: ipd.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>IPD Registration - Children Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #f4f6fa, #fdfdff);
      min-height: 100vh;
      margin: 0;
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
      padding: 2.5rem;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.06);
      max-width: 1080px;
      margin: auto;
      border: 1px solid rgba(255, 255, 255, 0.5);
    }

    h2 {
      color: #352f78;
      font-weight: 700;
      margin-bottom: 2rem;
    }

    label {
      color: #1f1f3c;
      font-weight: 600;
    }

    .form-control,
    .form-select {
      border-radius: 14px;
      border: 1px solid #ddd;
      transition: 0.3s all ease-in-out;
      background: #fff;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #8d79f6;
      box-shadow: 0 0 0 4px rgba(141, 121, 246, 0.1);
    }

    .btn-primary {
      background: linear-gradient(to right, #8d79f6, #7464ea);
      border: none;
      padding: 0.6rem 2rem;
      border-radius: 30px;
      font-weight: 600;
      color: white;
      transition: 0.3s;
    }

    .btn-primary:hover {
      opacity: 0.9;
    }

    .alert {
      border-radius: 12px;
      padding: 1rem 1.5rem;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
  </style>
  <script>
    function calculateFullAge() {
      const dob = document.getElementById('dob').value;
      if (dob) {
        const birth = new Date(dob);
        const today = new Date();
        let years = today.getFullYear() - birth.getFullYear();
        let months = today.getMonth() - birth.getMonth();
        let days = today.getDate() - birth.getDate();
        if (days < 0) { months--; days += 30; }
        if (months < 0) { years--; months += 12; }
        document.getElementById('age_display').innerText = `${years} Yrs`;
      }
    }
  </script>
</head>
<body>
<div class="main">
  <div class="glass-card">
    <h2>IPD Admission</h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="GET" class="row g-2 mb-4">
      <div class="col-md-4">
        <input type="text" name="mr_number" class="form-control" placeholder="Enter MR Number" required>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary">Search</button>
      </div>
    </form>

    <form class="row g-3" method="POST">
      <?= csrf_field() ?>

      <div class="col-md-4">
        <label>MR No *</label>
        <input type="text" name="mr_number" class="form-control" value="<?= htmlspecialchars($patient['mr_number'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label>IPD No *</label>
        <input type="text" name="ipd_no" class="form-control" value="<?= $ipd_no ?>" required>
      </div>
      <div class="col-md-4">
        <label>Patient Name</label>
        <input type="text" class="form-control" value="<?= $patient['name'] ?? '' ?>" readonly>
      </div>

      <div class="col-md-4">
        <label>DOB</label>
        <input type="date" class="form-control" id="dob" onchange="calculateFullAge()" value="<?= $patient['dob'] ?? '' ?>" readonly>
      </div>
      <div class="col-md-4">
        <label>Sex</label>
        <input type="text" class="form-control" value="<?= $patient['sex'] ?? '' ?>" readonly>
      </div>
      <div class="col-md-4">
        <label>Age</label>
        <div id="age_display" class="pt-2"><?= isset($patient['age']) ? $patient['age'] . " Yrs" : "" ?></div>
      </div>

      <div class="col-md-6">
        <label>Address</label>
        <input type="text" class="form-control" value="<?= $patient['address'] ?? '' ?>" readonly>
      </div>
      <div class="col-md-3">
        <label>Ward</label>
        <input type="text" class="form-control" name="ward">
      </div>
      <div class="col-md-3">
        <label>Bed No</label>
        <input type="text" class="form-control" name="bed">
      </div>

      <div class="col-md-4">
        <label>Department</label>
        <input type="text" class="form-control" value="<?= $patient['department'] ?? '' ?>" readonly>
      </div>
      <div class="col-md-4">
        <label>Doctor</label>
        <input type="text" class="form-control" name="doctor" value="<?= $patient['doctor'] ?? '' ?>">
      </div>
      <div class="col-md-4">
        <label>Referred By</label>
        <input type="text" class="form-control" name="ref_by">
      </div>

      <div class="col-md-3">
        <label>Admission Date</label>
        <input type="date" class="form-control" name="doa" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-md-3">
        <label>Admission Time</label>
        <input type="time" class="form-control" name="toa" value="<?= date('H:i') ?>">
      </div>
      <div class="col-md-3">
        <label>Discharge Date</label>
        <input type="date" class="form-control" name="dod">
      </div>
      <div class="col-md-3">
        <label>Discharge Time</label>
        <input type="time" class="form-control" name="tod">
      </div>

      <div class="col-md-4">
        <label>Email</label>
        <input type="email" class="form-control" name="email">
      </div>
      <div class="col-md-4">
        <label>Mobile</label>
        <input type="text" class="form-control" name="mobile" value="<?= $patient['mobile'] ?? '' ?>">
      </div>
      <div class="col-md-4">
        <label>Category</label>
        <input type="text" class="form-control" name="category" value="<?= $patient['category'] ?? '' ?>">
      </div>

      <div class="col-md-4">
        <label>Amount</label>
        <input type="number" step="0.01" class="form-control" name="amount">
      </div>

      <div class="col-12 text-end pt-3">
        <button class="btn btn-primary">Save IPD Record</button>
      </div>
    </form>
  </div>
</div>
<?php include('../includes/footer.php'); ?>
</body>
</html>