<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
hasRole('gynaecologist');

$success = $error = "";

// Generate MR No and OPD No
function generateYearlySequence($conn, $prefix, $column, $table) {
    $yearPart = date('Y');
    $like = "$prefix-$yearPart-%";

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE $column LIKE ?");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $next = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
    return "$prefix-$yearPart-$next";
}

$mr_number = generateYearlySequence($conn, "GYN-MR", "mr_number", "patients");
$opd_no = generateYearlySequence($conn, "GYN-OPD", "opd_no", "patients");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $mr = $_POST['mr_number'];
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $address = $_POST['address'];
    $mobile = $_POST['mobile'];
    $sex = $_POST['sex'];
    $category = $_POST['category'];
    $opd = $_POST['opd_no'];
    $opd_date = $_POST['opd_date'];
    $opd_time = $_POST['opd_time'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    $department = $_POST['department'];
    $doctor = $_POST['doctor'];

    $stmt = $conn->prepare("INSERT INTO patients (mr_number, name, dob, age, address, mobile, sex, category, opd_no, opd_date, opd_time, weight, height, department, doctor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissssissddss", $mr, $name, $dob, $age, $address, $mobile, $sex, $category, $opd, $opd_date, $opd_time, $weight, $height, $department, $doctor);

    if ($stmt->execute()) {
        $success = "New OPD patient registered successfully.";
        $mr_number = generateYearlySequence($conn, "GYN-MR", "mr_number", "patients");
        $opd_no = generateYearlySequence($conn, "GYN-OPD", "opd_no", "patients");
    } else {
        $error = "Failed to save patient: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>OPD Registration - Gynaecology</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: #fdf3f9;
      font-family: 'Segoe UI', sans-serif;
    }

    .container {
      max-width: 1000px;
      margin: auto;
      padding-top: 2rem;
    }

    h3 {
      color: #ba68c8;
      font-weight: 600;
    }

    .btn-primary {
      background-color: #ba68c8;
      border: none;
    }

    .btn-primary:hover {
      background-color: #aa47bc;
    }

    label {
      font-weight: 500;
    }
  </style>
  <script>
    function calculateAge() {
      const dob = document.getElementById('dob').value;
      if (dob) {
        const birthDate = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
          age--;
        }
        document.getElementById('age').value = age;
      }
    }
  </script>
</head>
<body>

<div class="container">
  <h3>New OPD Registration - Gynaecology</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form class="row g-3" method="POST">
    <?= csrf_field() ?>

    <div class="col-md-4">
      <label>MR Number</label>
      <input type="text" class="form-control" name="mr_number" value="<?= $mr_number ?>" readonly>
    </div>
    <div class="col-md-4">
      <label>OPD Number</label>
      <input type="text" class="form-control" name="opd_no" value="<?= $opd_no ?>" readonly>
    </div>
    <div class="col-md-4">
      <label>Patient Name</label>
      <input type="text" class="form-control" name="name" required>
    </div>

    <div class="col-md-4">
      <label>Date of Birth</label>
      <input type="date" class="form-control" id="dob" name="dob" onchange="calculateAge()" required>
    </div>
    <div class="col-md-2">
      <label>Age</label>
      <input type="number" class="form-control" id="age" name="age" readonly>
    </div>
    <div class="col-md-2">
      <label>Sex</label>
      <select name="sex" class="form-select" required>
        <option value="">Select</option>
        <option>Female</option>
        <option>Male</option>
        <option>Other</option>
      </select>
    </div>

    <div class="col-md-4">
      <label>Mobile</label>
      <input type="text" name="mobile" class="form-control" maxlength="15">
    </div>
    <div class="col-md-4">
      <label>Address</label>
      <input type="text" name="address" class="form-control">
    </div>
    <div class="col-md-4">
      <label>Category</label>
      <input type="text" name="category" class="form-control">
    </div>

    <div class="col-md-4">
      <label>Department</label>
      <select name="department" class="form-select">
        <option value="Gynae">Gynae</option>
        <option value="General">General</option>
        <option value="Maternity">Maternity</option>
      </select>
    </div>
    <div class="col-md-4">
      <label>Doctor</label>
      <input type="text" name="doctor" class="form-control">
    </div>
    <div class="col-md-2">
      <label>Weight (kg)</label>
      <input type="number" step="0.1" name="weight" class="form-control">
    </div>
    <div class="col-md-2">
      <label>Height (cm)</label>
      <input type="number" step="0.1" name="height" class="form-control">
    </div>
    <div class="col-md-4">
      <label>OPD Date</label>
      <input type="date" name="opd_date" class="form-control" value="<?= date('Y-m-d') ?>">
    </div>
    <div class="col-md-4">
      <label>OPD Time</label>
      <input type="time" name="opd_time" class="form-control" value="<?= date('H:i') ?>">
    </div>

    <div class="col-12 text-end">
      <button type="submit" class="btn btn-primary">Register Patient</button>
    </div>
  </form>
</div>

</body>
</html>
