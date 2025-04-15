<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
hasRole('children');
include('../includes/sidebar.php');

$success = $error = "";

function generateNextNumber($conn, $column, $table, $prefix) {
    $year = date('Y');
    $like = "$prefix-$year-%";
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM $table WHERE $column LIKE ?");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $next = str_pad($res['count'] + 1, 4, '0', STR_PAD_LEFT);
    return "$prefix-$year-$next";
}

$mr_number = generateNextNumber($conn, "mr_number", "patients", "MR");
$opd_no = generateNextNumber($conn, "opd_no", "patients", "OPD");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $mr = $_POST['mr_number'];
    $name = $_POST['name'] ?? null;
    $father_name = $_POST['father_name'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $age = $_POST['age'] ?? null;
    $address = $_POST['address'] ?? null;
    $mobile = $_POST['mobile'] ?? null;
    $alt_mobile = $_POST['alt_mobile'] ?? null;
    $sex = $_POST['sex'] ?? null;
    $category = $_POST['category'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $height = $_POST['height'] ?? null;
    $department = $_POST['department'] ?? null;
    $doctor = $_POST['doctor'] ?? null;
    $opd = $_POST['opd_no'];
    $opd_date = $_POST['opd_date'];
    $opd_time = $_POST['opd_time'];

    $stmt = $conn->prepare("INSERT INTO patients (
        mr_number, name, father_name, dob, age, address, mobile, alt_mobile, sex, category,
        weight, height, department, doctor, opd_no, opd_date, opd_time
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssisssssddsssss",
    $mr, $name, $father_name, $dob, $age, $address, $mobile, $alt_mobile,
    $sex, $category, $weight, $height, $department, $doctor,
    $opd, $opd_date, $opd_time
    );

    if ($stmt->execute()) {
        $success = "New OPD patient registered successfully.";
        $mr_number = generateNextNumber($conn, "mr_number", "patients", "MR");
        $opd_no = generateNextNumber($conn, "opd_no", "patients", "OPD");
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>OPD Registration - Children Hospital</title>
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

    .btn-secondary {
      background-color: #f0f0f5;
      color: #352f78;
      border: none;
      padding: 0.6rem 2rem;
      border-radius: 30px;
      font-weight: 600;
    }

    .alert {
      border-radius: 12px;
      padding: 1rem 1.5rem;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
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
<body class="bg-light">
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

    <div class="col-md-4">
      <label>MR Number</label>
      <input type="text" class="form-control" name="mr_number" value="<?= $mr_number ?>" readonly required>
    </div>
    <div class="col-md-4">
      <label>OPD Number</label>
      <input type="text" class="form-control" name="opd_no" value="<?= $opd_no ?>" readonly required>
    </div>
    <div class="col-md-4">
      <label>Patient Name</label>
      <input type="text" class="form-control" name="name">
    </div>
    <div class="col-md-4">
        <label>Father's Name</label>
        <input type="text" class="form-control" name="father_name">
    </div>
    <div class="col-md-4">
      <label>Date of Birth</label>
      <input type="date" class="form-control" id="dob" name="dob" onchange="calculateAge()">
    </div>
    <div class="col-md-4">
      <label>Age</label>
      <input type="text" class="form-control" id="age" name="age" readonly>
    </div>
    <div class="col-md-4">
      <label>Sex</label>
        <select name="sex" class="form-select">
          <option value="">Select</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>
      </div>
      <div class="col-md-4">
      <label>Mobile</label>
      <input type="text" name="mobile" class="form-control" maxlength="15">
    </div>
    <div class="col-md-4">
        <label>Alternate Mobile</label>
        <input type="text" class="form-control" name="alt_mobile" maxlength="15">
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
        <input type="text" class="form-control" name="department">
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
</div>
<?php include('../includes/footer.php'); ?>
</body>
</html>
