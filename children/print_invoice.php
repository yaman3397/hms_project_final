<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

// Function to convert numbers to words
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

// Get bill number from query parameter
$bill_no = $_GET['bill_no'] ?? '';
if (empty($bill_no)) {
    die("Error: Missing bill number");
}

// Initialize variables with default values
$invoice = [
    'mr_number' => '',
    'bill_no' => '',
    'date' => '',
    'patient_name' => '',
    'ipd_no' => '',
    'admit_date' => '',
    'admit_time' => '',
    'department' => '',
    'discharge_date' => '',
    'discharge_time' => '',
    'bed_no' => '',
    'doctor_name' => '',
    'age' => '',
    'sex' => '',
    'total_amount' => 0,
    'discount' => 0,
    'surcharge' => 0,
    'net_payable' => 0,
    'advance_paid' => 0,
    'final_amount' => 0,
    'balance' => 0
];

$services = [];

try {
    // Fetch invoice data
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE bill_no = ?");
    $stmt->bind_param("s", $bill_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Error: Invoice not found");
    }
    
    $invoice = $result->fetch_assoc();
    
    // Fetch service items
    $stmt = $conn->prepare("SELECT * FROM invoice_services WHERE bill_no = ?");
    $stmt->bind_param("s", $bill_no);
    $stmt->execute();
    $services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Invoice</title>
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

        .table td, .table th { padding: 4px 8px; font-size: 13px; }
        .label { font-weight: 600; color: #2e2e2e; font-size: 14px; }
        h5, h6 { font-weight: 700; color: #2a3f54; margin: 0; }
        table.table td, table.table th { vertical-align: top; border: 1px solid #dee2e6; }
        .mb-2 { border-left: 4px solid #8d79f6; padding-left: 10px; background: #f9f9ff; margin-bottom: 12px; }
        .no-print .btn-primary {
            background: linear-gradient(to right, #8d79f6, #7464ea);
            border: none;
            padding: 0.6rem 2rem;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            transition: 0.3s;
        }

        .no-print .btn-primary:hover {
            opacity: 0.9;
        }

        .no-print .btn-secondary {
            background-color: #f0f0f5;
            color: #352f78;
            border: none;
            padding: 0.6rem 2rem;
            border-radius: 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container mt-4" id="printable">
    <div class="text-center mb-3">
        <h5 class="fw-bold">SMILE INSTITUTE OF CHILD HEALTH & ATBC</h5>
        <p class="mb-1">Ramdaspeth, Birla Road, Akola</p>
        <h6 class="text-decoration-underline">BILL CUM RECEIPT</h6>
    </div>

    <table class="table table-sm">
        <tr><td><span class="label">MR No:</span> <?= htmlspecialchars($invoice['mr_number']) ?></td><td><span class="label">Bill No:</span> <?= htmlspecialchars($invoice['bill_no']) ?></td></tr>
        <tr><td><span class="label">Patient Name:</span> <?= htmlspecialchars($invoice['patient_name']) ?></td><td><span class="label">IPD No:</span> <?= htmlspecialchars($invoice['ipd_no']) ?></td></tr>
        <tr><td><span class="label">Admit Date:</span> <?= htmlspecialchars($invoice['admit_date']) ?> <?= htmlspecialchars($invoice['admit_time']) ?></td><td><span class="label">Discharge Date:</span> <?= htmlspecialchars($invoice['discharge_date']) ?> <?= htmlspecialchars($invoice['discharge_time']) ?></td></tr>
        <tr><td><span class="label">Department:</span> <?= htmlspecialchars($invoice['department']) ?></td><td><span class="label">Bed No:</span> <?= htmlspecialchars($invoice['bed_no']) ?></td></tr>
        <tr><td><span class="label">Doctor:</span> <?= htmlspecialchars($invoice['doctor_name']) ?></td><td><span class="label">Age / Sex:</span> <?= htmlspecialchars($invoice['age']) ?> / <?= htmlspecialchars($invoice['sex']) ?></td></tr>
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
                <td class="text-end"><?= htmlspecialchars($srv['quantity']) ?></td>
                <td class="text-end"><?= htmlspecialchars($srv['rate']) ?></td>
                <td class="text-end"><?= htmlspecialchars($srv['amount']) ?></td>
                <td class="text-end"><?= htmlspecialchars($srv['discount']) ?></td>
                <td class="text-end"><?= htmlspecialchars($srv['net_amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row mt-3">
        <div class="col-md-5"></div>
        <div class="col-md-7">
            <table class="table table-sm">
                <tr><td><strong>Total Bill:</strong></td><td class="text-end"><?= htmlspecialchars($invoice['total_amount']) ?></td></tr>
                <tr><td>Discount:</td><td class="text-end"><?= htmlspecialchars($invoice['discount']) ?></td></tr>
                <tr><td>Surcharge:</td><td class="text-end"><?= htmlspecialchars($invoice['surcharge']) ?></td></tr>
                <tr><td><strong>Net Payable:</strong></td><td class="text-end"><?= htmlspecialchars($invoice['net_payable']) ?></td></tr>
                <tr><td>Advance Paid:</td><td class="text-end"><?= htmlspecialchars($invoice['advance_paid']) ?></td></tr>
                <tr><td><strong>Final Amount:</strong></td><td class="text-end"><?= htmlspecialchars($invoice['final_amount']) ?></td></tr>
                <tr><td>Balance:</td><td class="text-end"><?= htmlspecialchars($invoice['balance']) ?></td></tr>
            </table>
        </div>
    </div>

    <div class="mt-2">
        <strong>Amount in Words:</strong>
        <?= numberToWords((int)($invoice['final_amount'])) ?>
    </div>

    <div class="text-end mt-4 fw-bold">
        Signature
    </div>

    <div class="d-flex justify-content-between mt-3">
        <small>Printed on <?= date('d/m/Y h:i A') ?></small>
        <small>Page 1/1</small>
    </div>
</div>

<div class="text-center mt-4 no-print">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <a href="invoice.php" class="btn btn-secondary">Back</a>
</div>
<?php include('../includes/footer.php'); ?>
</body>
</html>