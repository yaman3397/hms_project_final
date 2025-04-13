<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('gynaecologist');

$success = $error = "";
$data = [];
$print = false;

// Generate bill number
function getNextBillNo($conn) {
    $year = date('Y');
    $prefix = "INV-$year-";
    $result = $conn->query("SELECT MAX(bill_no) as max_no FROM gynae_invoices WHERE bill_no LIKE '$prefix%'");
    $row = $result->fetch_assoc();
    if ($row && $row['max_no']) {
        $last = (int)str_replace($prefix, '', $row['max_no']);
        return $prefix . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    } else {
        return $prefix . "0001";
    }
}

$generated_bill_no = getNextBillNo($conn);

// Fetch patient on MR search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $mr_number = trim($_POST['mr_number']);
    $stmt = $conn->prepare("
        SELECT 
            p.name, p.age, p.sex, p.department,
            i.ipd_no, i.doctor_name, i.admission_date, i.admission_time,
            i.discharge_date, i.discharge_time, i.ward_bed
        FROM gynae_patients p
        JOIN gynae_ipd_records i ON i.mr_number = p.mr_number
        WHERE p.mr_number = ?
        ORDER BY i.id DESC LIMIT 1
    ");
    $stmt->bind_param("s", $mr_number);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $data = $res->fetch_assoc();
        $data['mr_number'] = $mr_number;
    } else {
        $error = "No patient found for MR No: $mr_number";
    }
}

// Save invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_invoice'])) {
    $bill_no = $_POST['bill_no'];
    $mr_number = $_POST['mr_number'];
    if (empty($mr_number)) {
        $error = "MR No is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO gynae_invoices (
            bill_no, mr_number, patient_name, department, doctor_name, age, sex, date,
            ipd_no, admit_date, admit_time, discharge_date, discharge_time, bed_no,
            total_amount, discount, surcharge, net_payable, advance_paid, final_amount, balance
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssssssssssddddddd",
            $bill_no, $mr_number, $_POST['patient_name'], $_POST['department'], $_POST['doctor_name'],
            $_POST['age'], $_POST['sex'], $_POST['invoice_date'], $_POST['ipd_no'],
            $_POST['admit_date'], $_POST['admit_time'], $_POST['discharge_date'], $_POST['discharge_time'],
            $_POST['bed_no'], $_POST['total_amount'], $_POST['discount'], $_POST['surcharge'],
            $_POST['net_payable'], $_POST['advance_paid'], $_POST['final_amount'], $_POST['balance']
        );
        $stmt->execute();

        foreach ($_POST['service_name'] as $i => $srv) {
            $stmt2 = $conn->prepare("INSERT INTO gynae_invoice_services (
                bill_no, service_name, package_details, quantity, rate, amount, discount, net_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("sssddddd",
                $bill_no, $_POST['service_name'][$i], $_POST['package_details'][$i],
                $_POST['quantity'][$i], $_POST['rate'][$i], $_POST['amount'][$i],
                $_POST['row_discount'][$i], $_POST['net_amount'][$i]
            );
            $stmt2->execute();
        }

        header("Location: print_invoice.php?bill_no=" . urlencode($bill_no));
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gynae Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main { margin-left: 240px; padding: 2rem; }
        .btn-gynae { background: #f06292; color: white; }
        .btn-gynae:hover { background: #ec407a; }
    </style>
</head>
<body class="bg-light">
<div class="main">
    <h3 class="mb-3">Gynae Department - Invoice</h3>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- MR Search -->
    <form method="POST" class="mb-4 p-3 bg-white shadow rounded">
        <div class="row">
            <div class="col-md-6">
                <label class="form-label">MR No</label>
                <input type="text" name="mr_number" class="form-control" required>
            </div>
            <div class="col-md-6 align-self-end">
                <button type="submit" name="search" class="btn btn-gynae w-100">Fetch Patient</button>
            </div>
        </div>
    </form>

    <?php if (!empty($data)): ?>
    <form method="POST" class="bg-white p-4 shadow rounded">
        <input type="hidden" name="bill_no" value="<?= $generated_bill_no ?>">

        <div class="row g-2 mb-2">
            <div class="col-md-3"><label>Bill No</label><input type="text" class="form-control" value="<?= $generated_bill_no ?>" readonly></div>
            <div class="col-md-3"><label>Date</label><input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-3"><label>MR No</label><input type="text" name="mr_number" class="form-control" value="<?= $data['mr_number'] ?>" readonly></div>
            <div class="col-md-3"><label>Patient</label><input type="text" name="patient_name" class="form-control" value="<?= $data['name'] ?>" readonly></div>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-md-2"><label>Age</label><input type="text" name="age" class="form-control" value="<?= $data['age'] ?>" readonly></div>
            <div class="col-md-2"><label>Sex</label><input type="text" name="sex" class="form-control" value="<?= $data['sex'] ?>" readonly></div>
            <div class="col-md-2"><label>Doctor</label><input type="text" name="doctor_name" class="form-control" value="<?= $data['doctor_name'] ?>" readonly></div>
            <div class="col-md-2"><label>Dept</label><input type="text" name="department" class="form-control" value="<?= $data['department'] ?>" readonly></div>
            <div class="col-md-2"><label>IPD No</label><input type="text" name="ipd_no" class="form-control" value="<?= $data['ipd_no'] ?>" readonly></div>
            <div class="col-md-2"><label>Bed</label><input type="text" name="bed_no" class="form-control" value="<?= $data['ward_bed'] ?>"></div>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-md-3"><label>Admit Date</label><input type="date" name="admit_date" class="form-control" value="<?= $data['admission_date'] ?>" readonly></div>
            <div class="col-md-3"><label>Admit Time</label><input type="time" name="admit_time" class="form-control" value="<?= $data['admission_time'] ?>" readonly></div>
            <div class="col-md-3"><label>Discharge Date</label><input type="date" name="discharge_date" class="form-control" value="<?= $data['discharge_date'] ?>"></div>
            <div class="col-md-3"><label>Discharge Time</label><input type="time" name="discharge_time" class="form-control" value="<?= $data['discharge_time'] ?>"></div>
        </div>

        <h6 class="mt-3">Service Entries</h6>
        <table class="table table-bordered table-sm" id="servicesTable">
            <thead>
                <tr><th>Service</th><th>Package</th><th>Qty</th><th>Rate</th><th>Amt</th><th>Disc</th><th>Net</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><input name="service_name[]" class="form-control" required></td>
                    <td><input name="package_details[]" class="form-control"></td>
                    <td><input name="quantity[]" class="form-control qty" type="number" value="1"></td>
                    <td><input name="rate[]" class="form-control rate" type="number" value="0"></td>
                    <td><input name="amount[]" class="form-control amount" type="number" readonly></td>
                    <td><input name="row_discount[]" class="form-control row-discount" type="number" value="0"></td>
                    <td><input name="net_amount[]" class="form-control net" type="number" readonly></td>
                </tr>
            </tbody>
        </table>
        <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">+ Row</button>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow()">- Row</button>
        </div>


        <div class="row mt-3">
            <div class="col-md-3"><label>Total</label><input name="total_amount" id="total_amount" class="form-control" readonly></div>
            <div class="col-md-3"><label>Discount</label><input name="discount" id="discount" class="form-control" type="number" value="0"></div>
            <div class="col-md-3"><label>Surcharge</label><input name="surcharge" id="surcharge" class="form-control" type="number" value="0"></div>
            <div class="col-md-3"><label>Net Payable</label><input name="net_payable" id="net_payable" class="form-control" readonly></div>
            <div class="col-md-3"><label>Advance</label><input name="advance_paid" id="advance_paid" class="form-control" type="number" value="0"></div>
            <div class="col-md-3"><label>Final</label><input name="final_amount" id="final_amount" class="form-control" readonly></div>
            <div class="col-md-3"><label>Balance</label><input name="balance" id="balance" class="form-control" readonly></div>
        </div>

        <div class="text-end mt-4">
            <button type="submit" name="submit_invoice" class="btn btn-gynae">💾 Save & Print</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function addRow() {
    const tr = document.querySelector('#servicesTable tbody tr');
    const newRow = tr.cloneNode(true);
    newRow.querySelectorAll('input').forEach(inp => inp.value = inp.classList.contains('qty') ? 1 : inp.classList.contains('rate') ? 0 : '');
    tr.parentNode.appendChild(newRow);
}
function removeRow() {
  const rows = document.querySelectorAll('#servicesTable tbody tr');
  if (rows.length > 1) {
    rows[rows.length - 1].remove();
    recalc(); // Update totals
  }
}
document.addEventListener('input', e => {
    if (['qty','rate','row-discount','discount','surcharge','advance_paid'].some(c => e.target.classList.contains(c) || e.target.id === c)) recalc();
});
function recalc() {
    let total = 0;
    document.querySelectorAll('#servicesTable tbody tr').forEach(tr => {
        const qty = +tr.querySelector('.qty').value || 0;
        const rate = +tr.querySelector('.rate').value || 0;
        const disc = +tr.querySelector('.row-discount').value || 0;
        const amt = qty * rate;
        const net = amt - disc;
        tr.querySelector('.amount').value = amt;
        tr.querySelector('.net').value = net;
        total += net;
    });
    document.getElementById('total_amount').value = total;
    const discount = +document.getElementById('discount').value || 0;
    const surcharge = +document.getElementById('surcharge').value || 0;
    const adv = +document.getElementById('advance_paid').value || 0;
    const net = total + surcharge - discount;
    document.getElementById('net_payable').value = net;
    document.getElementById('final_amount').value = net;
    document.getElementById('balance').value = net - adv;
}
</script>
</body>
</html>
