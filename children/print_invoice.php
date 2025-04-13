<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
hasRole('children');

$bill_no = $_GET['bill_no'] ?? '';
if (!$bill_no) die("Missing bill_no");

// Fetch invoice
$stmt = $conn->prepare("SELECT * FROM invoices WHERE bill_no = ?");
$stmt->bind_param("s", $bill_no);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
if (!$invoice) die("Invoice not found");

// Fetch service items
$stmt = $conn->prepare("SELECT * FROM invoice_services WHERE bill_no = ?");
$stmt->bind_param("s", $bill_no);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Amount in words function (handles up to 99 crore)
function numberToWords($num) {
    $ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", 
             "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", 
             "Eighteen", "Nineteen"];
    $tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];

    if ($num == 0) {
        return "Rupees Zero Only";
    }

    $words = [];
    
    // Handle crores (1,00,00,000)
    if ($num >= 10000000) {
        $crores = floor($num / 10000000);
        $words[] = convertThreeDigits($crores, $ones, $tens) . " Crore";
        $num %= 10000000;
    }
    
    // Handle lakhs (1,00,000)
    if ($num >= 100000) {
        $lakhs = floor($num / 100000);
        $words[] = convertTwoDigits($lakhs, $ones, $tens) . " Lakh";
        $num %= 100000;
    }
    
    // Handle thousands (1,000)
    if ($num >= 1000) {
        $thousands = floor($num / 1000);
        $words[] = convertTwoDigits($thousands, $ones, $tens) . " Thousand";
        $num %= 1000;
    }
    
    // Handle hundreds (100)
    if ($num >= 100) {
        $hundreds = floor($num / 100);
        $words[] = $ones[$hundreds] . " Hundred";
        $num %= 100;
    }
    
    // Handle remaining amount (1-99)
    if ($num > 0) {
        $words[] = convertTwoDigits($num, $ones, $tens);
    }
    
    return "Rupees " . implode(" ", $words) . " Only";
}

function convertTwoDigits($num, $ones, $tens) {
    if ($num < 20) {
        return $ones[$num];
    } else {
        $ten = floor($num / 10);
        $unit = $num % 10;
        return $tens[$ten] . ($unit > 0 ? " " . $ones[$unit] : "");
    }
}

function convertThreeDigits($num, $ones, $tens) {
    $hundreds = floor($num / 100);
    $remainder = $num % 100;
    
    $result = [];
    if ($hundreds > 0) {
        $result[] = $ones[$hundreds] . " Hundred";
    }
    
    if ($remainder > 0) {
        $result[] = convertTwoDigits($remainder, $ones, $tens);
    }
    
    return implode(" ", $result);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            @page { size: A4 portrait; margin: 20mm; }
            body * { visibility: hidden; }
            #invoice, #invoice * { visibility: visible; }
            #invoice { position: absolute; top: 0; left: 0; width: 100%; font-size: 13px; }
            .no-print { display: none !important; }
        }
        .table-sm td, .table-sm th { padding: 5px; font-size: 13px; }
        .text-end { text-align: right; }
        .border-none td, .border-none th { border: none !important; }
    </style>
</head>
<body>
<div class="container my-4" id="invoice">
    <div class="text-center mb-3">
        <h5 class="fw-bold">SMILE INSTITUTE OF CHILD HEALTH & ATBC</h5>
        <p>Ramdaspeth, Birla Road, Akola</p>
        <h6 class="text-decoration-underline">BILL CUM RECEIPT</h6>
    </div>

    <table class="table table-sm border-none w-100 mb-2">
        <tr>
            <td><strong>MR No:</strong> <?= $invoice['mr_number'] ?></td>
            <td><strong>Bill No:</strong> <?= $invoice['bill_no'] ?></td>
            <td><strong>Date:</strong> <?= $invoice['date'] ?></td>
        </tr>
        <tr>
            <td><strong>Patient Name:</strong> <?= $invoice['patient_name'] ?></td>
            <td><strong>IPD No:</strong> <?= $invoice['ipd_no'] ?></td>
            <td><strong>Admit Date:</strong> <?= $invoice['admit_date'] ?> <?= $invoice['admit_time'] ?></td>
        </tr>
        <tr>
            <td><strong>Department:</strong> <?= $invoice['department'] ?></td>
            <td><strong>Discharge Date:</strong> <?= $invoice['discharge_date'] ?> <?= $invoice['discharge_time'] ?></td>
            <td><strong>Bed No:</strong> <?= $invoice['bed_no'] ?></td>
        </tr>
        <tr>
            <td><strong>Doctor:</strong> <?= $invoice['doctor_name'] ?></td>
            <td><strong>Age/Gender:</strong> <?= $invoice['age'] ?>/<?= $invoice['sex'] ?></td>
        </tr>
    </table>

    <hr>

    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>Service Name</th>
                <th>Package</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Amount</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Net</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $srv): ?>
            <tr>
                <td><?= htmlspecialchars($srv['service_name']) ?></td>
                <td><?= htmlspecialchars($srv['package_details']) ?></td>
                <td class="text-end"><?= $srv['quantity'] ?></td>
                <td class="text-end"><?= $srv['rate'] ?></td>
                <td class="text-end"><?= $srv['amount'] ?></td>
                <td class="text-end"><?= $srv['discount'] ?></td>
                <td class="text-end"><?= $srv['net_amount'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row mt-3">
        <div class="col-md-5"></div>
        <div class="col-md-7">
            <table class="table table-sm">
                <tr><td><strong>Total Bill:</strong></td><td class="text-end"><?= $invoice['total_amount'] ?></td></tr>
                <tr><td>Discount:</td><td class="text-end"><?= $invoice['discount'] ?></td></tr>
                <tr><td>Surcharge:</td><td class="text-end"><?= $invoice['surcharge'] ?></td></tr>
                <tr><td><strong>Net Payable:</strong></td><td class="text-end"><?= $invoice['net_payable'] ?></td></tr>
                <tr><td>Advance Paid:</td><td class="text-end"><?= $invoice['advance_paid'] ?></td></tr>
                <tr><td><strong>Final Amount:</strong></td><td class="text-end"><?= $invoice['final_amount'] ?></td></tr>
                <tr><td>Balance:</td><td class="text-end"><?= $invoice['balance'] ?></td></tr>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <strong>Amount in Words:</strong>
        <?= numberToWords((int)($invoice['final_amount'])) ?>
    </div>

    <div class="text-end mt-4 fw-bold">
        Signature
    </div>
    <hr>
    <div class="d-flex justify-content-between">
        <small>Printed on <?= date('d/m/Y h:i A') ?></small>
        <small>Page 1/1</small>
    </div>
</div>

<div class="text-center mt-4 no-print">
    <button class="btn btn-primary" onclick="window.print()">Print Invoice</button>
    <a href="invoice.php" class="btn btn-secondary">Back</a>
</div>
</body>
</html>