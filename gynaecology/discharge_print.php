<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
hasRole('gynaecologist');

$discharge_id = $_GET['id'] ?? '';
if (!$discharge_id) die("Missing discharge ID");

$stmt = $conn->prepare("SELECT * FROM gynae_discharge_summary WHERE discharge_id = ?");
$stmt->bind_param("s", $discharge_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) die("Discharge summary not found.");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Discharge Summary - Gynae</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            @page { size: A4 portrait; margin: 20mm; }
            body * { visibility: hidden; }
            #printable, #printable * { visibility: visible; }
            #printable { position: absolute; top: 0; left: 0; width: 100%; font-size: 13px; }
            .no-print { display: none !important; }
        }

        .section-title {
            background: #f3e5f5;
            font-weight: bold;
            padding: 6px;
        }
        .value-box {
            border-bottom: 1px solid #ddd;
            padding: 4px 0;
        }
    </style>
</head>
<body>
<div class="container my-4" id="printable">
    <div class="text-center mb-4">
        <h5 class="fw-bold text-uppercase">SMILE INSTITUTE OF GYNAECOLOGY</h5>
        <p>Ramdaspeth, Birla Road, Akola</p>
        <h6 class="text-decoration-underline">Discharge Summary</h6>
    </div>

    <table class="table table-bordered table-sm">
        <tr><td><strong>Discharge ID:</strong> <?= $data['discharge_id'] ?></td><td><strong>MR No:</strong> <?= $data['mr_number'] ?></td></tr>
        <tr><td><strong>Patient Name:</strong> <?= $data['patient_name'] ?></td><td><strong>IPD No:</strong> <?= $data['ipd_no'] ?></td></tr>
        <tr><td><strong>Age/Sex:</strong> <?= $data['age'] ?>/<?= $data['sex'] ?></td><td><strong>DOB:</strong> <?= $data['dob'] ?></td></tr>
        <tr><td><strong>Doctor:</strong> <?= $data['doctor_name'] ?></td><td><strong>Department:</strong> <?= $data['department'] ?></td></tr>
        <tr><td><strong>Admission:</strong> <?= $data['admission_date'] ?> <?= $data['admission_time'] ?></td><td><strong>Discharge:</strong> <?= $data['discharge_date'] ?> <?= $data['discharge_time'] ?></td></tr>
        <tr><td><strong>Ward:</strong> <?= $data['ward'] ?></td><td><strong>Room No:</strong> <?= $data['room_no'] ?></td></tr>
        <tr><td><strong>Email:</strong> <?= $data['email'] ?></td><td><strong>Mobile:</strong> <?= $data['mobile'] ?></td></tr>
        <tr><td><strong>Ref By:</strong> <?= $data['ref_by'] ?></td><td><strong>Address:</strong> <?= $data['address'] ?></td></tr>
    </table>

    <div class="mt-4">
        <div class="section-title">Diagnosis</div>
        <div class="value-box"><?= nl2br($data['diagnosis']) ?></div>

        <div class="section-title">Weight</div>
        <div class="value-box"><?= $data['weight'] ?></div>

        <div class="section-title">Reason for Admission</div>
        <div class="value-box"><?= nl2br($data['reason']) ?></div>

        <div class="section-title">Significant Findings</div>
        <div class="value-box"><?= nl2br($data['findings']) ?></div>

        <div class="section-title">Procedure / Operation</div>
        <div class="value-box"><?= nl2br($data['procedure']) ?></div>

        <div class="section-title">Treatment Given</div>
        <div class="value-box"><?= nl2br($data['treatment_given']) ?></div>

        <div class="section-title">Condition at Discharge</div>
        <div class="value-box"><?= nl2br($data['discharge_condition']) ?></div>

        <div class="section-title">Treatment Advised</div>
        <div class="value-box"><?= nl2br($data['treatment_advised']) ?></div>

        <div class="section-title">Second Opinion</div>
        <div class="value-box"><?= nl2br($data['second_opinion']) ?></div>

        <div class="section-title">Urgent Care Advice</div>
        <div class="value-box"><?= nl2br($data['urgent_care']) ?></div>

        <div class="section-title">Follow-up Advice</div>
        <div class="value-box"><?= nl2br($data['follow_up']) ?></div>
    </div>

    <div class="text-end mt-4 fw-bold">
        Prepared By
    </div>

    <hr>
    <div class="d-flex justify-content-between">
        <small>Printed on <?= date('d/m/Y h:i A') ?></small>
        <small>Page 1/1</small>
    </div>
</div>

<div class="text-center mt-4 no-print">
    <button class="btn btn-primary" onclick="window.print()">Print Summary</button>
    <a href="discharge.php" class="btn btn-outline-secondary">Back</a>
</div>
</body>
</html>
