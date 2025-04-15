<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$discharge_id = $_GET['discharge_id'] ?? '';
$patient_name = $_GET['patient_name'] ?? '';
$discharge_date = $_GET['discharge_date'] ?? '';

$query = "SELECT discharge_id, mr_number, ipd_no, patient_name, doctor_name, discharge_date FROM discharge_summary";
$conditions = [];
$params = [];
$types = '';

if (!empty($discharge_id)) {
    $conditions[] = "discharge_id LIKE ?";
    $params[] = "%" . $discharge_id . "%";
    $types .= 's';
}
if (!empty($patient_name)) {
    $conditions[] = "patient_name LIKE ?";
    $params[] = "%" . $patient_name . "%";
    $types .= 's';
}
if (!empty($discharge_date)) {
    $conditions[] = "discharge_date = ?";
    $params[] = $discharge_date;
    $types .= 's';
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" OR ", $conditions) . " ORDER BY discharge_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("$query ORDER BY discharge_date DESC");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Children Hospital - Discharge Summary</title>
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
        <h2>Discharge Summary List</h2>
        <form method="GET" class="mb-4 row g-3">
            <div class="col-md-4">
                <input type="text" name="discharge_id" class="form-control" placeholder="Discharge ID" value="<?= htmlspecialchars($_GET['discharge_id'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="patient_name" class="form-control" placeholder="Patient Name" value="<?= htmlspecialchars($_GET['patient_name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="date" name="discharge_date" class="form-control" value="<?= htmlspecialchars($_GET['discharge_date'] ?? '') ?>">
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Discharge ID</th>
                    <th>MR Number</th>
                    <th>IPD Number</th>
                    <th>Patient Name</th>
                    <th>Doctor</th>
                    <th>Discharge Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['discharge_id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['mr_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['ipd_no'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['patient_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['doctor_name'] ?? '') ?></td>
                            <td><?= !empty($row['discharge_date']) ? date('d-m-Y', strtotime($row['discharge_date'])) : '' ?></td>
                            <td>
                                <a href="discharge_print.php?print_id=<?= urlencode($row['discharge_id']) ?>" target="_blank" class="btn btn-print">Print</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No discharge summaries found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include('../includes/footer.php'); ?>
</body>
</html>