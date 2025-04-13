<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$success = $error = "";
$data = [];
$submittedData = [];
$print_id = $_GET['print_id'] ?? '';

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

// Fetch data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $mr_number = trim($_POST['mr_number'] ?? '');
    $stmt = $conn->prepare("
        SELECT 
            p.mr_number, p.name AS patient_name, p.dob, p.age, p.sex, p.address, p.mobile, p.category,
            p.department, p.doctor AS doctor_name,
            i.ipd_no, i.ward_bed AS ward, i.admission_date, i.admission_time,
            i.discharge_date, i.discharge_time, i.email, i.ref_by, i.amount, i.diagnosis
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
        $error = "No record found for MR Number: $mr_number";
    }
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_discharge'])) {
    $discharge_id = getNextDischargeId($conn);
    $stmt = $conn->prepare("INSERT INTO discharge_summary (
        discharge_id, mr_number, ipd_no, patient_name, age, sex, dob, doctor_name,
        admission_date, admission_time, discharge_date, discharge_time,
        mobile, email, ref_by, department, ward, room_no, address, diagnosis,
        weight, reason, findings, `procedure`, treatment_given,
        discharge_condition, treatment_advised, second_opinion,
        urgent_care, follow_up
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssssssssssssssssssssssssss",
        $discharge_id, $_POST['mr_number'], $_POST['ipd_no'], $_POST['patient_name'], $_POST['age'], $_POST['sex'],
        $_POST['dob'], $_POST['doctor_name'], $_POST['admission_date'], $_POST['admission_time'],
        $_POST['discharge_date'], $_POST['discharge_time'], $_POST['mobile'], $_POST['email'], $_POST['ref_by'],
        $_POST['department'], $_POST['ward'], $_POST['room_no'], $_POST['address'], $_POST['diagnosis'],
        $_POST['weight'], $_POST['reason'], $_POST['findings'], $_POST['procedure'], $_POST['treatment_given'],
        $_POST['discharge_condition'], $_POST['treatment_advised'], $_POST['second_opinion'],
        $_POST['urgent_care'], $_POST['follow_up']
    );

    if ($stmt->execute()) {
        $_SESSION['discharge_id'] = $discharge_id;
        $_SESSION['success'] = "Discharge summary saved successfully.";
        header("Location: discharge.php?print_id=$discharge_id");
        exit;
    } else {
        $error = "Error saving summary: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Discharge Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main { margin-left: 240px; padding: 2rem; }
        #savePrintBtn { position: relative; }
        .spinner-border { 
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
</head>
<body class="bg-light">
<div class="main">
    <h3>Discharge Summary</h3>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- MR Search -->
    <form method="POST" class="mb-4 p-3 bg-white rounded shadow-sm">
        <label class="form-label">MR No.</label>
        <div class="input-group">
            <input type="text" name="mr_number" class="form-control" required>
            <button type="submit" name="search" class="btn btn-primary">Fetch Patient</button>
        </div>
    </form>

    <?php if (!empty($data)): ?>
    <form method="POST" id="dischargeForm" class="bg-white p-3 rounded shadow-sm">
        <input type="hidden" name="save_discharge" value="1">
        <div class="row g-3">
            <!-- Pre-filled and editable fields -->
            <div class="col-md-4"><label>MR No</label><input type="text" name="mr_number" class="form-control" value="<?= htmlspecialchars($data['mr_number']) ?>" required></div>
            <div class="col-md-4"><label>Patient Name</label><input type="text" name="patient_name" class="form-control" value="<?= htmlspecialchars($data['patient_name'] ?? '') ?>"></div>
            <div class="col-md-2"><label>Age</label><input type="text" name="age" class="form-control" value="<?= htmlspecialchars($data['age'] ?? '') ?>"></div>
            <div class="col-md-2"><label>DOB</label><input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($data['dob'] ?? '') ?>"></div>
            <div class="col-md-2"><label>Sex</label><input type="text" name="sex" class="form-control" value="<?= htmlspecialchars($data['sex'] ?? '') ?>"></div>
            <div class="col-md-2"><label>Mobile</label><input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($data['mobile'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Ref By</label><input type="text" name="ref_by" class="form-control" value="<?= htmlspecialchars($data['ref_by'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Doctor</label><input type="text" name="doctor_name" class="form-control" value="<?= htmlspecialchars($data['doctor_name'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Department</label><input type="text" name="department" class="form-control" value="<?= htmlspecialchars($data['department'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Ward</label><input type="text" name="ward" class="form-control" value="<?= htmlspecialchars($data['ward'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Room No</label><input type="text" name="room_no" class="form-control"></div>
            <div class="col-md-4"><label>IPD No</label><input type="text" name="ipd_no" class="form-control" value="<?= htmlspecialchars($data['ipd_no'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Admission Date</label><input type="date" name="admission_date" class="form-control" value="<?= htmlspecialchars($data['admission_date'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Admission Time</label><input type="time" name="admission_time" class="form-control" value="<?= htmlspecialchars($data['admission_time'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Discharge Date</label><input type="date" name="discharge_date" class="form-control" value="<?= htmlspecialchars($data['discharge_date'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Discharge Time</label><input type="time" name="discharge_time" class="form-control" value="<?= htmlspecialchars($data['discharge_time'] ?? '') ?>"></div>
            <div class="col-md-4"><label>Diagnosis</label><textarea name="diagnosis" class="form-control"><?= htmlspecialchars($data['diagnosis'] ?? '') ?></textarea></div>
            <div class="col-md-4"><label>Weight</label><input type="text" name="weight" class="form-control"></div>
            <div class="col-md-12"><label>Reason for Admission</label><textarea name="reason" class="form-control"></textarea></div>
            <div class="col-md-12"><label>Significant Findings</label><textarea name="findings" class="form-control"></textarea></div>
            <div class="col-md-12"><label>Procedure / Operation</label><textarea name="procedure" class="form-control"></textarea></div>
            <div class="col-md-12"><label>Treatment Given</label><textarea name="treatment_given" class="form-control"></textarea></div>
            <div class="col-md-12"><label>Condition at Discharge</label><textarea name="discharge_condition" class="form-control"></textarea></div>
            <div class="col-md-12"><label>Treatment Advised</label><textarea name="treatment_advised" class="form-control"></textarea></div>
            <div class="col-md-12"><label>Second Opinion</label><textarea name="second_opinion" class="form-control"></textarea></div>
            <div class="col-md-12"><label>Urgent Care Advice</label><textarea name="urgent_care" class="form-control"></textarea></div>
            <div class="col-md-12"><label>Follow-up Advice</label><textarea name="follow_up" class="form-control"></textarea></div>
        </div>

        <div class="text-end mt-4">
            <button type="submit" id="savePrintBtn" class="btn btn-success">
                Save & Print Summary
                <span class="spinner-border spinner-border-sm d-none" id="saveSpinner"></span>
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
document.getElementById('dischargeForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('savePrintBtn');
    const spinner = document.getElementById('saveSpinner');
    
    if (btn && spinner) {
        btn.disabled = true;
        spinner.classList.remove('d-none');
    }
});

<?php if (!empty($_SESSION['discharge_id'])): ?>
    // Open print window after successful save
    window.onload = function() {
        window.open('discharge_print.php?id=<?= $_SESSION['discharge_id'] ?>', '_blank');
        <?php unset($_SESSION['discharge_id']); ?>
    };
<?php endif; ?>
</script>
</body>
</html>