<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$bill_no = $_GET['bill_no'] ?? '';
$patient_name = $_GET['patient_name'] ?? '';
$invoice_date = $_GET['invoice_date'] ?? '';

$query = "SELECT bill_no, mr_number, ipd_no, patient_name, doctor_name, date FROM invoices";
$conditions = [];
$params = [];
$types = '';

if (!empty($bill_no)) {
    $conditions[] = "bill_no LIKE ?";
    $params[] = "%" . $bill_no . "%";
    $types .= 's';
}
if (!empty($patient_name)) {
    $conditions[] = "patient_name LIKE ?";
    $params[] = "%" . $patient_name . "%";
    $types .= 's';
}
if (!empty($invoice_date)) {
    $conditions[] = "date = ?";
    $params[] = $invoice_date;
    $types .= 's';
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" OR ", $conditions) . " ORDER BY date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("$query ORDER BY date DESC");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Children Hospital - Invoice List</title>
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
            margin-bottom: 2rem;
        }

        label {
            color: #1f1f3c;
            font-weight: 600;
        }

        .form-control {
            border-radius: 14px;
            border: 1px solid #ddd;
            transition: 0.3s all ease-in-out;
            background: #fff;
        }

        .form-control:focus {
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

        .btn-print {
            background: linear-gradient(to right, #8d79f6, #7464ea);
            border: none;
            padding: 4px 14px;
            font-size: 13px;
            border-radius: 8px;
            color: #fff;
        }

        .btn-print:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
<div class="main">
    <div class="glass-card">
        <h2>Invoice List</h2>
        <form method="GET" class="mb-4 row g-3">
            <div class="col-md-4">
                <input type="text" name="bill_no" class="form-control" placeholder="Bill No" value="<?= htmlspecialchars($_GET['bill_no'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="patient_name" class="form-control" placeholder="Patient Name" value="<?= htmlspecialchars($_GET['patient_name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="date" name="invoice_date" class="form-control" value="<?= htmlspecialchars($_GET['invoice_date'] ?? '') ?>">
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Bill No</th>
                    <th>MR Number</th>
                    <th>IPD Number</th>
                    <th>Patient Name</th>
                    <th>Doctor</th>
                    <th>Invoice Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['bill_no'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['mr_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['ipd_no'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['patient_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['doctor_name'] ?? '') ?></td>
                            <td><?= !empty($row['date']) ? date('d-m-Y', strtotime($row['date'])) : '' ?></td>
                            <td>
                                <a href="print_invoice.php?bill_no=<?= urlencode($row['bill_no']) ?>" target="_blank" class="btn btn-print">Print</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No invoices found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include('../includes/footer.php'); ?>
</body>
</html>