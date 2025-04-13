<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
hasRole('children');

$success = $error = "";

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

$mr_number = generateYearlySequence($conn, "MR", "mr_number", "patients");
$opd_no = generateYearlySequence($conn, "OPD", "opd_no", "patients");

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
        $mr_number = generateYearlySequence($conn, "MR", "mr_number", "patients");
        $opd_no = generateYearlySequence($conn, "OPD", "opd_no", "patients");
    } else {
        $error = "Failed to save patient: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>OPD Registration - Children Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(to right, #fdfbfb, #ebedee);
      min-height: 100vh;
    }

    .layout {
      display: flex;
    }

    .sidebar {
      width: 240px;
      background: white;
      box-shadow: 2px 0 15px rgba(0,0,0,0.05);
      padding: 2rem 1rem;
    }

    .sidebar h3 {
      font-weight: 600;
      margin-bottom: 3rem;
      text-align: center;
      color: #333;
    }

    .sidebar a {
      display: block;
      padding: 12px 20px;
      margin-bottom: 10px;
      color: #333;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 500;
      transition: 0.3s;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background: linear-gradient(135deg, #f3f4f6, #e0e7ff);
      color: #4f46e5;
    }

    .main {
      flex: 1;
      padding: 3rem;
    }

    .glass-card {
      background: white;
      border-radius: 20px;
      padding: 2rem 2.5rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
      max-width: 1100px;
      margin: auto;
    }

    h2 {
      color: #4f46e5;
      font-weight: 600;
      margin-bottom: 2rem;
    }

    label {
      color: #111827;
      font-weight: 500;
    }

    .form-control, .form-select {
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      transition: box-shadow 0.3s, border-color 0.3s;
    }

    .form-control:focus, .form-select:focus {
      border-color: #4f46e5;
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .btn-primary {
      background: linear-gradient(to right, #7f5af0, #2cb67d);
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

    .btn-secondary {
      background-color: #e5e7eb;
      color: #111827;
      border-radius: 30px;
      font-weight: 600;
    }

    .alert {
      border-radius: 10px;
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

<div class="layout">
  <div class="sidebar">
    <h3>Children HMS</h3>
    <a href="#">Dashboard</a>
    <a href="#" class="active">New OPD</a>
    <a href="#">Existing Patients</a>
    <a href="#">Doctors</a>
    <a href="#">Reports</a>
    <a href="#">Logout</a>
  </div>

  <div class="main">
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="glass-card">
      <h2>New OPD Registration</h2>
      <form class="row g-3" method="POST">
        <?= csrf_field() ?>

        <div class="col-md-6">
          <label>Patient Name *</label>
          <input type="text" class="form-control" name="name" required>
        </div>
        <div class="col-md-6">
          <label>Mobile Number *</label>
          <input type="text" class="form-control" name="mobile" maxlength="15">
        </div>
        <div class="col-md-12">
          <label>Address</label>
          <input type="text" class="form-control" name="address">
        </div>
        <div class="col-md-4">
          <label>Date of Birth *</label>
          <input type="date" class="form-control" id="dob" name="dob" onchange="calculateAge()" required>
        </div>
        <div class="col-md-2">
          <label>Age</label>
          <input type="number" class="form-control" id="age" name="age" readonly>
        </div>
        <div class="col-md-2">
          <label>Sex *</label>
          <select name="sex" class="form-select" required>
            <option value="">Select</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
          </select>
        </div>
        <div class="col-md-4">
          <label>Category</label>
          <input type="text" class="form-control" name="category">
        </div>
        <div class="col-md-4">
          <label>OPD Date</label>
          <input type="date" name="opd_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-4">
          <label>OPD Time</label>
          <input type="time" name="opd_time" class="form-control" value="<?= date('H:i') ?>">
        </div>
        <div class="col-md-4">
          <label>Weight (kg)</label>
          <input type="number" step="0.1" name="weight" class="form-control">
        </div>
        <div class="col-md-4">
          <label>Height (cm)</label>
          <input type="number" step="0.1" name="height" class="form-control">
        </div>
        <div class="col-md-4">
          <label>Doctor *</label>
          <input type="text" class="form-control" name="doctor">
        </div>
        <div class="col-md-6">
          <label>MR Number</label>
          <input type="text" class="form-control" name="mr_number" value="<?= $mr_number ?>" readonly>
        </div>
        <div class="col-md-6">
          <label>OPD Number</label>
          <input type="text" class="form-control" name="opd_no" value="<?= $opd_no ?>" readonly>
        </div>

        <div class="col-12 d-flex justify-content-between pt-3">
          <button type="reset" class="btn btn-secondary">Reset</button>
          <button type="submit" class="btn btn-primary">Register Patient</button>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>
