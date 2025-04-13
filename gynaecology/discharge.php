<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('gynaecologist');

$success = $error = "";
$data = [];
$submittedData = [];
$print_id = $_GET['print_id'] ?? '';

// Generate discharge ID
function getNextDischargeId($conn) {
    $year = date('Y');
    $prefix = "DISGYN-$year-";
    $res = $conn->query("SELECT MAX(discharge_id) as max_id FROM gynae_discharge_summary WHERE discharge_id LIKE '$prefix%'");
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
        FROM gynae_patients p
        JOIN gynae_ipd_records i ON p.mr_number = i.mr_number
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
    $stmt = $conn->prepare("INSERT INTO gynae_discharge_summary (
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
    <title>Gynae Discharge Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main { margin-left: 240px; padding: 2rem; }
    </style>
</head>
<body class="bg-light">
<div class="main">
    <h3>Discharge Summary - Gynaecology</h3>

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
    <form method="POST" class="bg-white p-3 rounded shadow-sm">
        <input type="hidden" name="save_discharge" value="1">
        <div class="row g-3">
            <?php
            $fields = [
                'mr_number' => 'MR Number', 'patient_name' => 'Patient Name', 'age' => 'Age', 'dob' => 'DOB',
                'sex' => 'Sex', 'mobile' => 'Mobile', 'email' => 'Email', 'ref_by' => 'Ref By',
                'doctor_name' => 'Doctor', 'department' => 'Department', 'ward' => 'Ward',
                'room_no' => 'Room No', 'ipd_no' => 'IPD No', 'admission_date' => 'Admission Date',
                'admission_time' => 'Admission Time', 'discharge_date' => 'Discharge Date',
                'discharge_time' => 'Discharge Time', 'address' => 'Address', 'diagnosis' => 'Diagnosis',
                'weight' => 'Weight', 'reason' => 'Reason for Admission', 'findings' => 'Significant Findings',
                'procedure' => 'Procedure / Operation', 'treatment_given' => 'Treatment Given',
                'discharge_condition' => 'Condition at Discharge', 'treatment_advised' => 'Treatment Advised',
                'second_opinion' => 'Second Opinion', 'urgent_care' => 'Urgent Care Advice',
                'follow_up' => 'Follow-up Advice'
            ];
            foreach ($fields as $key => $label):
                $value = $data[$key] ?? '';
                $tag = (in_array($key, ['reason', 'findings', 'procedure', 'treatment_given', 'discharge_condition', 'treatment_advised', 'second_opinion', 'urgent_care', 'follow_up', 'address', 'diagnosis'])) ? 'textarea' : 'input';
                echo '<div class="col-md-'. ($tag === 'textarea' ? '12' : '4') .'">';
                echo "<label>$label</label>";
                if ($tag === 'input') {
                    $type = in_array($key, ['dob', 'admission_date', 'discharge_date']) ? 'date' :
                            (in_array($key, ['admission_time', 'discharge_time']) ? 'time' :
                            (strpos($key, 'email') !== false ? 'email' : 'text'));
                    echo "<input type=\"$type\" name=\"$key\" class=\"form-control\" value=\"".htmlspecialchars($value)."\">";
                } else {
                    echo "<textarea name=\"$key\" class=\"form-control\" rows=\"2\">".htmlspecialchars($value)."</textarea>";
                }
                echo "</div>";
            endforeach;
            ?>
        </div>

        <div class="text-end mt-4">
            <button type="submit" class="btn btn-success">Save and Print</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if (!empty($print_id)): ?>
    <div class="text-end mt-3">
        <a href="discharge_print.php?id=<?= htmlspecialchars($print_id) ?>" class="btn btn-outline-secondary" target="_blank">Print Summary</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
