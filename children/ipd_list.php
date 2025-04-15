<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$mr_number = $_GET['mr_number'] ?? '';
$ipd_no = $_GET['ipd_no'] ?? '';
$doctor = $_GET['doctor'] ?? '';

$query = "SELECT ir.*, p.name, p.dob, p.sex, p.age, p.address, p.department, p.mobile, p.category FROM ipd_records ir LEFT JOIN patients p ON ir.mr_number = p.mr_number WHERE ir.is_deleted = 0";
$conditions = [];
$params = [];
$types = '';

if (!empty($mr_number)) {
    $conditions[] = "mr_number LIKE ?";
    $params[] = "%" . $mr_number . "%";
    $types .= 's';
}
if (!empty($ipd_no)) {
    $conditions[] = "ipd_no LIKE ?";
    $params[] = "%" . $ipd_no . "%";
    $types .= 's';
}
if (!empty($doctor)) {
    $conditions[] = "doctor_name LIKE ?";
    $params[] = "%" . $doctor . "%";
    $types .= 's';
}

if (!empty($conditions)) {
    $query .= " AND (" . implode(" OR ", $conditions) . ")";
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) as total FROM ipd_records ir LEFT JOIN patients p ON ir.mr_number = p.mr_number WHERE ir.is_deleted = 0";
if (!empty($conditions)) {
    $count_query .= " AND (" . implode(" OR ", $conditions) . ")";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];

    $sort_order = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $sort_field = 'id'; // default sorting field
    $query .= " ORDER BY $sort_field $sort_order LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $total_result = $conn->query("SELECT COUNT(*) as total FROM ipd_records ir LEFT JOIN patients p ON ir.mr_number = p.mr_number WHERE ir.is_deleted = 0");
    $total_records = $total_result->fetch_assoc()['total'];
    $sort_order = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $result = $conn->query("SELECT ir.*, p.name, p.dob, p.sex, p.age, p.address, p.department, p.mobile, p.category FROM ipd_records ir LEFT JOIN patients p ON ir.mr_number = p.mr_number WHERE ir.is_deleted = 0 ORDER BY ir.id $sort_order LIMIT $limit OFFSET $offset");
}

$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Children Hospital - IPD List</title>
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
            width: 100%;
            max-width: 100%;
            margin: auto;
            border: 1px solid rgba(255, 255, 255, 0.5);
            overflow-x: auto;
        }
        h2 {
            color: #352f78;
            font-weight: 700;
            margin-bottom: 2rem;
        }
        .btn-edit, .btn-delete {
            border: none;
            padding: 4px 12px;
            font-size: 13px;
            border-radius: 8px;
            color: #fff;
        }
        .btn-edit {
            background: linear-gradient(to right, #9b86f7, #7860e6);
        }
        .btn-delete {
            background: linear-gradient(to right, #f67280, #e55060);
        }
        .btn-edit:hover, .btn-delete:hover {
            opacity: 0.9;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table thead th {
            background-color: #f4f4f9;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .btn-primary {
            background: linear-gradient(to right, #8d79f6, #7464ea);
            border: none;
            border-radius: 30px;
            font-weight: 600;
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        .pagination .page-link {
            color: #7464ea;
            border-radius: 30px;
            transition: 0.3s;
        }
        .pagination .page-item.active .page-link {
            background: linear-gradient(to right, #8d79f6, #7464ea);
            border-color: transparent;
            color: #fff;
            font-weight: 600;
        }
        .pagination .page-link:hover {
            background: #edeaff;
            color: #7464ea;
        }
        @media (max-width: 768px) {
            .main {
                margin-left: 0;
                padding: 1rem;
            }
            .glass-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="main">
    <div class="glass-card">
        <h2>IPD Patient List</h2>
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" name="mr_number" class="form-control" placeholder="MR Number" value="<?= htmlspecialchars($_GET['mr_number'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="ipd_no" class="form-control" placeholder="IPD Number" value="<?= htmlspecialchars($_GET['ipd_no'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="doctor" class="form-control" placeholder="Doctor Name" value="<?= htmlspecialchars($_GET['doctor'] ?? '') ?>">
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
        <div class="row mb-3">
            <div class="col-md-4">
                <form method="GET" class="d-flex align-items-center">
                    <input type="hidden" name="mr_number" value="<?= htmlspecialchars($_GET['mr_number'] ?? '') ?>">
                    <input type="hidden" name="ipd_no" value="<?= htmlspecialchars($_GET['ipd_no'] ?? '') ?>">
                    <input type="hidden" name="doctor" value="<?= htmlspecialchars($_GET['doctor'] ?? '') ?>">
                    <label for="sort" class="me-2 fw-semibold">Sort By:</label>
                    <select class="form-select form-select-sm" name="sort" id="sort" onchange="this.form.submit()" style="max-width: 200px;">
                        <option value="">Default</option>
                        <option value="asc" <?= ($_GET['sort'] ?? '') === 'asc' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="desc" <?= ($_GET['sort'] ?? '') === 'desc' ? 'selected' : '' ?>>Newest First</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="table-responsive">
        <table class="table table-bordered table-hover" style="min-width: 1200px;">
            <thead class="table-light">
                <tr>
                    <th>MR Number</th>
                    <th>IPD Number</th>
                    <th>Name</th>
                    <th>DOB</th>
                    <th>Sex</th>
                    <th>Age</th>
                    <th>Address</th>
                    <th>Department</th>
                    <th>Mobile</th>
                    <th>Category</th>
                    <th>Ward/Bed</th>
                    <th>Doctor</th>
                    <th>Admission Date</th>
                    <th>Admission Time</th>
                    <th>Discharge Date</th>
                    <th>Discharge Time</th>
                    <th>Email</th>
                    <th>Ref By</th>
                    <th>Amount</th>
                    <th>Diagnosis</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['mr_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['ipd_no'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['dob'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['sex'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['age'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['address'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['department'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['mobile'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['category'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['ward_bed'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['doctor_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['admission_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['admission_time'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['discharge_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['discharge_time'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['ref_by'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['amount'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['diagnosis'] ?? '') ?></td>
                            <td>
                                <button class="btn btn-edit">Edit</button>
                                <button class="btn btn-delete">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="13" class="text-center">No IPD records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <div class="mt-3 text-center">
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>
<script>
function alertBox(message, success = true) {
    const alertDiv = document.createElement('div');
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.left = '50%';
    alertDiv.style.transform = 'translateX(-50%)';
    alertDiv.style.background = success ? '#d4edda' : '#f8d7da';
    alertDiv.style.color = success ? '#155724' : '#721c24';
    alertDiv.style.border = '1px solid ' + (success ? '#c3e6cb' : '#f5c6cb');
    alertDiv.style.padding = '12px 24px';
    alertDiv.style.borderRadius = '14px';
    alertDiv.style.boxShadow = '0 6px 20px rgba(0,0,0,0.1)';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.fontSize = '15px';
    alertDiv.innerText = message;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 3000);
}

function makeRowEditable(button) {
    const row = button.closest('tr');
    const mrNumber = row.children[0].innerText.trim();

    const fieldTypes = {
        'admission_date': 'date',
        'admission_time': 'time',
        'discharge_date': 'date',
        'discharge_time': 'time',
        'amount': 'number'
    };

    const fields = [
        'ward_bed', 'doctor_name', 'admission_date', 'admission_time',
        'discharge_date', 'discharge_time', 'email', 'ref_by', 'amount', 'diagnosis'
    ];

    for (let i = 0; i < fields.length; i++) {
        const cell = row.children[i + 10];
        const val = cell.innerText.trim();
        const field = document.createElement('input');
        field.type = fieldTypes[fields[i]] || 'text';
        field.className = 'form-control form-control-sm';
        field.value = val;
        field.dataset.original = val;
        cell.innerHTML = '';
        cell.appendChild(field);
    }

    button.textContent = 'Save';
    button.onclick = () => saveRow(row, mrNumber, button);
}

function saveRow(row, mrNumber, button) {
    const ipdNo = row.children[1]?.innerText?.trim();
    if (!ipdNo) {
        alertBox("IPD Number is missing or invalid.", false);
        return;
    }

    const fields = [
        'ward_bed', 'doctor_name', 'admission_date', 'admission_time',
        'discharge_date', 'discharge_time', 'email', 'ref_by', 'amount', 'diagnosis'
    ];

    const data = {
        action: 'update',
        table: 'ipd_records',
        key: 'ipd_no',
        value: ipdNo,
        fields: {}
    };

    for (let i = 0; i < fields.length; i++) {
        const input = row.children[i + 10].querySelector('input');
        if (!input) continue;

        const fieldName = fields[i];
        let val = input.value.trim();

        if (fieldName === 'amount') {
            data.fields[fieldName] = val === '' ? null : parseFloat(val);
        } else {
            data.fields[fieldName] = val === '' ? null : val;
        }
    }

    fetch('common_update_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(resp => resp.json())
    .then(response => {
        if (response.success) {
            for (let i = 0; i < fields.length; i++) {
                row.children[i + 10].innerText = data.fields[fields[i]] ?? '';
            }
            button.textContent = 'Edit';
            button.onclick = () => makeRowEditable(button);
            alertBox("Record updated successfully.");
        } else {
            alertBox("Failed to update record: " + (response.message || 'Unknown error'), false);
        }
    }).catch(() => alertBox("Error occurred during update.", false));
}

function deleteRow(ipdNo, button) {
    if (!confirm("Are you sure you want to delete this patient?")) return;

    fetch('common_update_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'delete',
            table: 'ipd_records',
            key: 'ipd_no',
            value: ipdNo
        })
    }).then(resp => resp.json())
    .then(response => {
        if (response.success) {
            const row = button.closest('tr');
            row.remove();
            alertBox("Record deleted successfully.");
        } else {
            alertBox("Failed to delete record: " + (response.message || 'Unknown error'), false);
        }
    }).catch(() => alertBox("Error occurred during deletion.", false));
}

// Attach handlers
document.querySelectorAll('.btn-edit').forEach(btn => btn.onclick = () => makeRowEditable(btn));
document.querySelectorAll('.btn-delete').forEach(btn => {
    const ipdNo = btn.closest('tr').children[1].innerText.trim(); // IPD Number at index 1
    btn.onclick = () => deleteRow(ipdNo, btn);
});
</script>
<?php include('../includes/footer.php'); ?>
</body>
</html>
