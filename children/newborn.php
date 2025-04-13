<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$success = $error = "";

// Flash message
if (isset($_SESSION['success'])) {
  $success = $_SESSION['success'];
  unset($_SESSION['success']);
}

// Generate Baby MR No
function generateBabyMR($conn) {
  $year = date('Y');
  $prefix = "BMR-$year-";
  $like = "$prefix%";
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM newborns WHERE baby_mr LIKE ?");
  $stmt->bind_param("s", $like);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  return $prefix . str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
}

// Generate Baby OPD No
function generateBabyOPD($conn) {
  $year = date('Y');
  $prefix = "BOPD-$year-";
  $like = "$prefix%";
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM newborns WHERE baby_opd_no LIKE ?");
  $stmt->bind_param("s", $like);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  return $prefix . str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
}

$baby_mr = $_POST['baby_mr'] ?? generateBabyMR($conn);
$baby_opd_no = $_POST['baby_opd_no'] ?? generateBabyOPD($conn);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  $mother_mr = $_POST['mother_mr'];
  $dob = $_POST['dob'];

  // Strict backend DOB validation
  if (empty($dob)) {
    $error = "Date of Birth is required.";
  } else {
    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
      $error = "Invalid date format. Please use YYYY-MM-DD format.";
    } else {
      // Check if date is valid
      $dateParts = explode('-', $dob);
      if (!checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
        $error = "Invalid date. Please enter a valid date.";
      }
    }
  }

  if (empty($error)) {
    // Check if mother's MR exists
    $stmt = $conn->prepare("SELECT * FROM patients WHERE mr_number = ?");
    $stmt->bind_param("s", $mother_mr);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
      $error = "Mother's MR No not found in OPD.";
    } else {
      $baby_mr = $_POST['baby_mr'];
      $baby_opd_no = $_POST['baby_opd_no'];
      $name = $_POST['name'];
      $age = $_POST['age'];
      $address = $_POST['address'];
      $mobile = $_POST['mobile'];
      $sex = $_POST['sex'];
      $category = $_POST['category'];
      $opd_date = $_POST['opd_date'];
      $opd_time = $_POST['opd_time'];
      $weight = $_POST['weight'];
      $height = $_POST['height'];
      $department = $_POST['department'];
      $doctor = $_POST['doctor'];

      try {
        $stmt = $conn->prepare("INSERT INTO newborns (
          baby_mr, baby_opd_no, mother_mr, baby_name, dob, age, address, mobile, sex,
          category, opd_date, opd_time, weight, height, department, doctor
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssssisssssissss",
          $baby_mr, $baby_opd_no, $mother_mr, $name, $dob, $age, $address, $mobile, $sex,
          $category, $opd_date, $opd_time, $weight, $height, $department, $doctor
        );

        if ($stmt->execute()) {
          $_SESSION['success'] = "Newborn registered successfully.";
          header("Location: newborn.php");
          exit();
        }
      } catch (mysqli_sql_exception $e) {
        $error = "Database Error: " . $e->getMessage();
        error_log("Database Error: " . $e->getMessage());
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Newborn Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>.main { margin-left: 240px; padding: 2rem; }</style>
  <script>
    function calculateAge() {
      const dob = document.getElementById('dob').value;
      if (dob) {
        const birth = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
          age--;
        }
        document.getElementById('age').value = age;
      }
    }
    
    // Client-side date validation
    function validateDate(input) {
      const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
      if (!dateRegex.test(input.value)) {
        input.setCustomValidity('Please enter date in YYYY-MM-DD format');
      } else {
        input.setCustomValidity('');
      }
    }
  </script>
</head>
<body class="bg-light">
<div class="main">
  <h3 class="mb-4">Newborn Registration</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="row g-3" onsubmit="return validateForm()">
    <?= csrf_field() ?>

    <div class="col-md-4">
      <label>Baby MR Number</label>
      <input type="text" name="baby_mr" class="form-control" value="<?= htmlspecialchars($baby_mr) ?>" readonly>
    </div>
    <div class="col-md-4">
      <label>Baby OPD Number</label>
      <input type="text" name="baby_opd_no" class="form-control" value="<?= htmlspecialchars($baby_opd_no) ?>" readonly>
    </div>
    <div class="col-md-4">
      <label>Mother's MR Number</label>
      <input type="text" name="mother_mr" class="form-control" value="<?= htmlspecialchars($_POST['mother_mr'] ?? '') ?>" required>
    </div>

    <div class="col-md-4">
      <label>Baby Name</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
    </div>
    <div class="col-md-4">
      <label>Date of Birth</label>
      <input type="date" name="dob" id="dob" class="form-control" 
             onchange="calculateAge(); validateDate(this)"
             value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" 
             required pattern="\d{4}-\d{2}-\d{2}" 
             title="Format: YYYY-MM-DD">
    </div>
    <div class="col-md-2">
      <label>Age</label>
      <input type="number" name="age" id="age" class="form-control" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>" readonly>
    </div>
    <div class="col-md-2">
      <label>Sex</label>
      <select name="sex" class="form-select" required>
        <option value="">Select</option>
        <option value="Male" <?= ($_POST['sex'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= ($_POST['sex'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
      </select>
    </div>

    <div class="col-md-4">
      <label>Mobile Number</label>
      <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label>Address</label>
      <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label>Category</label>
      <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($_POST['category'] ?? '') ?>">
    </div>

    <div class="col-md-4">
      <label>OPD Date</label>
      <input type="date" name="opd_date" class="form-control" value="<?= htmlspecialchars($_POST['opd_date'] ?? date('Y-m-d')) ?>" required>
    </div>
    <div class="col-md-4">
      <label>OPD Time</label>
      <input type="time" name="opd_time" class="form-control" value="<?= htmlspecialchars($_POST['opd_time'] ?? date('H:i')) ?>">
    </div>

    <div class="col-md-2">
      <label>Weight (kg)</label>
      <input type="number" step="0.1" name="weight" class="form-control" value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label>Height (cm)</label>
      <input type="number" step="0.1" name="height" class="form-control" value="<?= htmlspecialchars($_POST['height'] ?? '') ?>">
    </div>

    <div class="col-md-6">
      <label>Department</label>
      <select name="department" class="form-select" required>
        <option value="">Select Department</option>
        <option value="Neonatal" <?= ($_POST['department'] ?? '') === 'Neonatal' ? 'selected' : '' ?>>Neonatal</option>
        <option value="NICU" <?= ($_POST['department'] ?? '') === 'NICU' ? 'selected' : '' ?>>NICU</option>
      </select>
    </div>
    <div class="col-md-6">
      <label>Doctor</label>
      <input type="text" name="doctor" class="form-control" value="<?= htmlspecialchars($_POST['doctor'] ?? '') ?>" required>
    </div>

    <div class="col-12 text-end">
      <button type="submit" class="btn btn-success">Register Newborn</button>
    </div>
  </form>
</div>

<script>
  // Form validation before submission
  function validateForm() {
    const dob = document.getElementById('dob').value;
    if (!dob) {
      alert('Please enter Date of Birth');
      return false;
    }
    
    // Additional validation can be added here
    return true;
  }
</script>
</body>
</html>