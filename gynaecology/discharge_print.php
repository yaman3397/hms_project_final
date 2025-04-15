<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('gynaecologist');


$discharge_id = $_GET['print_id'] ?? '';

if (!$discharge_id) {
    echo "<div style='margin: 2rem; font-family: sans-serif; color: red;'>Discharge ID is missing in the URL. Please go back and try again.</div>";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM gynae_discharge_summary WHERE discharge_id = ?");
$stmt->bind_param("s", $discharge_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) die("Discharge summary not found.");

// Helper function to safely display data
function safeDisplay($value) {
    return $value !== null ? nl2br(htmlspecialchars($value)) : '';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Discharge Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #fff;
        }

        .container {
            max-width: 900px;
            padding: 20px;
            background-color: #fefefe;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        @media print {
            @page { size: A4 portrait; margin: 20mm; }
            body * { visibility: hidden; }
            #printable, #printable * { visibility: visible; }
            #printable { position: absolute; top: 0; left: 0; width: 100%; font-size: 13px; }
            .no-print { display: none !important; }
        }
        .btn-primary {
      background: linear-gradient(to right, #c0507d, #a03d67);
      border: none;
      border-radius: 30px;
      font-weight: 600;
    }

    .btn-primary:hover {
      opacity: 0.9;
    }

        .table td, .table th { padding: 4px 8px; font-size: 13px; }
        .label { font-weight: 600; color: #2e2e2e; font-size: 14px; }
        h5, h6 { font-weight: 700; color: #2a3f54; margin: 0; }
        table.table td, table.table th { vertical-align: top; border: 1px solid #dee2e6; }
        .mb-2 { border-left: 4px solid #ba3e6a; padding-left: 10px; background: #f9f9ff; margin-bottom: 12px; }
        .no-print .btn { margin: 0 8px; }
    </style>
</head>
<body>
<div class="container mt-4" id="printable">
    <div class="text-center mb-3">
        <h5 class="fw-bold">SMILE INSTITUTE OF CHILD HEALTH & ATBC</h5>
        <p class="mb-1">Ramdaspeth, Birla Road, Akola</p>
        <h6 class="text-decoration-underline">Discharge Summary</h6>
    </div>

    <table class="table table-sm">
        <tr>
            <td><span class="label">MR No:</span> <?= safeDisplay($data['mr_number']) ?></td>
            <td><span class="label">IPD No:</span> <?= safeDisplay($data['ipd_no']) ?></td>
        </tr>
        <tr>
            <td><span class="label">Patient Name:</span> <?= safeDisplay($data['patient_name']) ?></td>
            <td><span class="label">Age / Sex:</span> <?= safeDisplay($data['age']) ?> / <?= safeDisplay($data['sex']) ?></td>
        </tr>
        <tr>
            <td><span class="label">Mobile:</span> <?= safeDisplay($data['mobile']) ?></td>
            <td><span class="label">Email:</span> <?= safeDisplay($data['email']) ?></td>
        </tr>
        <tr>
            <td><span class="label">Doctor:</span> <?= safeDisplay($data['doctor_name']) ?></td>
            <td><span class="label">Department:</span> <?= safeDisplay($data['department']) ?></td>
        </tr>
        <tr>
            <td><span class="label">Ward:</span> <?= safeDisplay($data['ward']) ?></td>
            <td><span class="label">Room No:</span> <?= safeDisplay($data['room_no']) ?></td>
        </tr>
        <tr>
            <td><span class="label">Admission Date:</span> <?= safeDisplay($data['admission_date']) ?> <?= safeDisplay($data['admission_time']) ?></td>
            <td><span class="label">Discharge Date:</span> <?= safeDisplay($data['discharge_date']) ?> <?= safeDisplay($data['discharge_time']) ?></td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Discharge Type:</span> <?= safeDisplay($data['discharge_type']) ?></td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Address:</span> <?= safeDisplay($data['address']) ?></td>
        </tr>
    </table>

    <hr>

    <div class="mb-2"><span class="label">Diagnosis:</span><br><?= safeDisplay($data['diagnosis']) ?></div>
    <div class="mb-2"><span class="label">Reason for Admission:</span><br><?= safeDisplay($data['reason']) ?></div>
    <div class="mb-2"><span class="label">Significant Findings:</span><br><?= safeDisplay($data['findings']) ?></div>
    <div class="mb-2"><span class="label">Procedure / Operation:</span><br><?= safeDisplay($data['procedure']) ?></div>
    <div class="mb-2"><span class="label">Treatment Given:</span><br><?= safeDisplay($data['treatment_given']) ?></div>
    <div class="mb-2"><span class="label">Condition at Discharge:</span><br><?= safeDisplay($data['discharge_condition']) ?></div>
    <div class="mb-2"><span class="label">Treatment Advised:</span><br><?= safeDisplay($data['treatment_advised']) ?></div>
    <div class="mb-2"><span class="label">Second Opinion:</span><br><?= safeDisplay($data['second_opinion']) ?></div>
    <div class="mb-2"><span class="label">Urgent Care:</span><br><?= safeDisplay($data['urgent_care']) ?></div>
    <div class="mb-2"><span class="label">Follow-up Advice:</span><br><?= safeDisplay($data['follow_up']) ?></div>

    <div class="text-end mt-4">
        <p class="mb-0"><strong>Doctor's Signature</strong></p>
        <hr style="width: 200px; margin-left: auto;">
    </div>

    <div class="d-flex justify-content-between mt-3">
        <small>Printed on <?= date('d/m/Y h:i A') ?></small>
        <small>Page 1/1</small>
    </div>
</div>

<div class="text-center mt-4 no-print">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <a href="discharge.php" class="btn btn-secondary">Back</a>
</div>
<?php include('../includes/footer.php'); ?>
</body>
</html>