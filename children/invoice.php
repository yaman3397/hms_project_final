<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$success = $error = "";
$data = [];

// Generate next bill number
function getNextBillNo($conn) {
    $year = date('Y');
    $prefix = "INV-$year-";

    $result = $conn->query("SELECT MAX(bill_no) as max_no FROM invoices WHERE bill_no LIKE 'INV-$year-%'");
    $row = $result->fetch_assoc();
    if ($row && $row['max_no']) {
        $last = (int)str_replace($prefix, '', $row['max_no']);
        return $prefix . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    } else {
        return $prefix . "0001";
    }
}

// Fetch patient data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $mr_number = trim($_POST['mr_number']);
    $stmt = $conn->prepare("
        SELECT p.name, p.age, p.sex, p.department,
               i.ipd_no, i.doctor_name, i.admission_date, i.admission_time,
               i.discharge_date, i.discharge_time, i.ward_bed
        FROM patients p
        JOIN ipd_records i ON i.mr_number = p.mr_number
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
        $error = "No record found for MR No: $mr_number";
    }
}

// Handle invoice save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_invoice'])) {
    $bill_no = $_POST['bill_no'];
    $mr_number = $_POST['mr_number'];

    if (empty($mr_number)) {
        $error = "MR No. is required.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO invoices (
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
                $stmt2 = $conn->prepare("INSERT INTO invoice_services (
                    bill_no, service_name, package_details, quantity, rate, amount, discount, net_amount
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("sssddddd",
                    $bill_no, $_POST['service_name'][$i], $_POST['package_details'][$i],
                    $_POST['quantity'][$i], $_POST['rate'][$i], $_POST['amount'][$i],
                    $_POST['row_discount'][$i], $_POST['net_amount'][$i]
                );
                $stmt2->execute();
            }

            $_SESSION['success'] = "Invoice saved successfully.";
            header("Location: print_invoice.php?bill_no=$bill_no");
            exit;

        } catch (Exception $e) {
            $error = "Error saving invoice: " . $e->getMessage();
        }
    }
}

// Generate bill number
$generated_bill_no = getNextBillNo($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
      margin-bottom: 1.5rem;
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

    .alert {
      border-radius: 12px;
      padding: 1rem 1.5rem;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    </style>
</head>
<body class="bg-light">
<div class="main">
    <div class="glass-card">
        <h2>Invoice Generation</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- MR Search -->
        <div class="glass-card mb-4">
            <form method="POST" class="row g-2 mb-4">
                <div class="col-md-4">
                    <input type="text" name="mr_number" class="form-control" placeholder="Enter MR Number" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        <?php if (!empty($data)): ?>
        <form method="POST" class="bg-white p-3 rounded shadow-sm">
            <input type="hidden" name="bill_no" value="<?= $generated_bill_no ?>">

            <div class="row g-3">
                <div class="col-md-3"><label>Bill No</label><input type="text" class="form-control" value="<?= $generated_bill_no ?>" readonly></div>
                <div class="col-md-3"><label>Invoice Date</label><input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-3"><label>MR No</label><input type="text" name="mr_number" class="form-control" value="<?= $data['mr_number'] ?>" readonly></div>
                <div class="col-md-3"><label>Patient Name</label><input type="text" name="patient_name" class="form-control" value="<?= $data['name'] ?>" readonly></div>
                <div class="col-md-2"><label>Age</label><input type="text" name="age" class="form-control" value="<?= $data['age'] ?>" readonly></div>
                <div class="col-md-2"><label>Sex</label><input type="text" name="sex" class="form-control" value="<?= $data['sex'] ?>" readonly></div>
                <div class="col-md-2"><label>Department</label><input type="text" name="department" class="form-control" value="<?= $data['department'] ?>" readonly></div>
                <div class="col-md-2"><label>Doctor</label><input type="text" name="doctor_name" class="form-control" value="<?= $data['doctor_name'] ?>" readonly></div>
                <div class="col-md-2"><label>IPD No</label><input type="text" name="ipd_no" class="form-control" value="<?= $data['ipd_no'] ?>" readonly></div>
                <div class="col-md-2"><label>Bed No</label><input type="text" name="bed_no" class="form-control" value="<?= $data['ward_bed'] ?>"></div>
                <div class="col-md-2"><label>Admit Date</label><input type="date" name="admit_date" class="form-control" value="<?= $data['admission_date'] ?>" readonly></div>
                <div class="col-md-2"><label>Admit Time</label><input type="time" name="admit_time" class="form-control" value="<?= $data['admission_time'] ?>" readonly></div>
                <div class="col-md-2"><label>Discharge Date</label><input type="date" name="discharge_date" class="form-control" value="<?= htmlspecialchars($data['discharge_date']) ?>"></div>
                <div class="col-md-2"><label>Discharge Time</label><input type="time" name="discharge_time" class="form-control" value="<?= htmlspecialchars($data['discharge_time']) ?>"></div>
            </div>

            <hr>

            <!-- Services Table -->
            <h6>Services</h6>
            <table class="table table-bordered table-sm" id="servicesTable">
                <thead>
                    <tr><th>Service</th><th>Package</th><th>Qty</th><th>Rate</th><th>Amount</th><th>Discount</th><th>Net</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="service_name[]" class="form-control" required></td>
                        <td><input type="text" name="package_details[]" class="form-control"></td>
                        <td><input type="number" name="quantity[]" class="form-control qty" value="1"></td>
                        <td><input type="number" name="rate[]" class="form-control rate" value="0"></td>
                        <td><input type="number" name="amount[]" class="form-control amount" readonly></td>
                        <td><input type="number" name="row_discount[]" class="form-control row-discount" value="0"></td>
                        <td><input type="number" name="net_amount[]" class="form-control net" readonly></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">+ Add Row</button>

            <hr>

            <div class="row g-3">
                <div class="col-md-3"><label>Total Bill Amount</label><input type="number" name="total_amount" id="total_amount" class="form-control" readonly></div>
                <div class="col-md-3"><label>Discount</label><input type="number" name="discount" id="discount" class="form-control" value="0"></div>
                <div class="col-md-3"><label>Surcharge</label><input type="number" name="surcharge" id="surcharge" class="form-control" value="0"></div>
                <div class="col-md-3"><label>Net Payable</label><input type="number" name="net_payable" id="net_payable" class="form-control" readonly></div>
                <div class="col-md-3"><label>Advance Paid</label><input type="number" name="advance_paid" id="advance_paid" class="form-control" value="0"></div>
                <div class="col-md-3"><label>Final Amount</label><input type="number" name="final_amount" id="final_amount" class="form-control" readonly></div>
                <div class="col-md-3"><label>Balance</label><input type="number" name="balance" id="balance" class="form-control" readonly></div>
            </div>

            <div class="text-end pt-3">
                <button type="submit" name="submit_invoice" class="btn btn-primary">Save & Print Invoice</button>
            </div>

        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function addRow() {
    const tr = document.querySelector("#servicesTable tbody tr");
    const clone = tr.cloneNode(true);
    clone.querySelectorAll("input").forEach(i => i.value = i.classList.contains("qty") ? 1 : i.classList.contains("rate") || i.classList.contains("row-discount") ? 0 : "");
    tr.parentNode.appendChild(clone);
}

document.addEventListener('input', function(e) {
    if (['qty', 'rate', 'row-discount', 'discount', 'surcharge', 'advance_paid'].some(cls => e.target.classList.contains(cls) || e.target.id === cls)) {
        recalc();
    }
});

function recalc() {
    let total = 0;
    document.querySelectorAll("#servicesTable tbody tr").forEach(tr => {
        const qty = +tr.querySelector(".qty").value || 0;
        const rate = +tr.querySelector(".rate").value || 0;
        const disc = +tr.querySelector(".row-discount").value || 0;
        const amt = qty * rate;
        const net = amt - disc;
        tr.querySelector(".amount").value = amt;
        tr.querySelector(".net").value = net;
        total += net;
    });

    document.getElementById("total_amount").value = total;
    const discount = +document.getElementById("discount").value || 0;
    const surcharge = +document.getElementById("surcharge").value || 0;
    const adv = +document.getElementById("advance_paid").value || 0;
    const net = total + surcharge - discount;
    document.getElementById("net_payable").value = net;
    document.getElementById("final_amount").value = net;
    document.getElementById("balance").value = net - adv;
}
</script>
<?php include('../includes/footer.php'); ?>
</body>
</html>
