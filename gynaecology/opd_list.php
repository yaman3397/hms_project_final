<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('gynaecologist');

$mr_number = $_GET['mr_number'] ?? '';
$name = $_GET['name'] ?? '';
$opd_date = $_GET['opd_date'] ?? '';

$query = "SELECT * FROM gynae_patients";
$conditions = [];
$params = [];
$types = '';

if (!empty($mr_number)) {
    $conditions[] = "mr_number LIKE ?";
    $params[] = "%" . $mr_number . "%";
    $types .= 's';
}
if (!empty($name)) {
    $conditions[] = "name LIKE ?";
    $params[] = "%" . $name . "%";
    $types .= 's';
}
if (!empty($opd_date)) {
    $conditions[] = "opd_date = ?";
    $params[] = $opd_date;
    $types .= 's';
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) as total FROM gynae_patients";
if (!empty($conditions)) {
    $count_query .= " WHERE " . implode(" OR ", $conditions);
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $total_records = $count_result['total'];

    $sort_order = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $query .= " WHERE " . implode(" OR ", $conditions) . " ORDER BY created_at $sort_order LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $total_result = $conn->query("SELECT COUNT(*) as total FROM gynae_patients");
    $total_records = $total_result->fetch_assoc()['total'];
    $sort_order = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $result = $conn->query("SELECT * FROM gynae_patients ORDER BY created_at $sort_order LIMIT $limit OFFSET $offset");
}

$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html>
<head>
    <title>OPD Patient List</title>
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
            overflow-x: auto; /* Enables horizontal scroll */
        }

        h2 {
      color: #692146;
      font-weight: 700;
      margin-bottom: 2rem;
    }

        .btn-edit, .btn-delete {
            border: none;
            padding: 4px 12px;
            font-size: 13px;
            border-radius: 8px;
            color: #fff;
            width: 80px;
            text-align: center;
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
      background: linear-gradient(to right, #c0507d, #a03d67);
      border: none;
      border-radius: 30px;
      font-weight: 600;
    }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .pagination .page-link {
            color: #a03d67;
            border-radius: 30px;
            transition: 0.3s;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(to right, #c0507d, #a03d67);
            border-color: transparent;
            color: #fff;
            font-weight: 600;
        }

        .pagination .page-link:hover {
            background: #edeaff;
            color:  #a03d67;
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
        <h2>OPD Patient List</h2>
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" name="mr_number" class="form-control" placeholder="MR Number" value="<?= htmlspecialchars($_GET['mr_number'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" placeholder="Patient Name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="date" name="opd_date" class="form-control" value="<?= htmlspecialchars($_GET['opd_date'] ?? '') ?>">
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
        <div class="row mb-3">
            <div class="col-md-4">
                <form method="GET" class="d-flex align-items-center">
                    <input type="hidden" name="mr_number" value="<?= htmlspecialchars($_GET['mr_number'] ?? '') ?>">
                    <input type="hidden" name="name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
                    <input type="hidden" name="opd_date" value="<?= htmlspecialchars($_GET['opd_date'] ?? '') ?>">
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
                    <th>OPD Number</th>
                    <th>Name</th>
                    <th>DOB</th>
                    <th>Age</th>
                    <th>Sex</th>
                    <th>Mobile</th>
                    <th>Alternate Mobile</th>
                    <th>Address</th>
                    <th>Category</th>
                    <th>Department</th>
                    <th>Doctor</th>
                    <th>Weight</th>
                    <th>Height</th>
                    <th>OPD Date</th>
                    <th>OPD Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['mr_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['opd_no'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['dob'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['age'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['sex'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['mobile'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['alt_mobile'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['address'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['category'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['department'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['doctor'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['weight'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['height'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['opd_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['opd_time'] ?? '') ?></td>
                            <td>
                                <button class="btn btn-edit" onclick="makeRowEditable(this)">Edit</button>
                                <button class="btn btn-delete" onclick="deleteRow('<?= $row['mr_number'] ?>', this)">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="17" class="text-center">No patients found.</td></tr>
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

    for (let i = 2; i <= 15; i++) { // Skipping MR No and OPD No
        const cell = row.children[i];
        const val = cell.innerText.trim();
        const field = document.createElement('input');
        field.type = 'text';
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
    const fields = [
        'name', 'dob', 'age', 'sex', 'mobile', 'alt_mobile',
        'address', 'category', 'department', 'doctor', 'weight', 'height',
        'opd_date', 'opd_time'
    ];

    const data = {
        action: 'update',
        table: 'gynae_patients',
        key: 'mr_number',
        value: mrNumber,
        fields: {}
    };

    for (let i = 0; i < fields.length; i++) {
        const inputField = row.children[i + 2].querySelector('input');
        if (inputField) {
            data.fields[fields[i]] = inputField.value;
        } else {
            data.fields[fields[i]] = row.children[i + 2].innerText.trim(); // fallback
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
                row.children[i + 2].innerText = data.fields[fields[i]];
            }
            button.textContent = 'Edit';
            button.onclick = () => makeRowEditable(button);
            alertBox("Record updated successfully.");
        } else {
            alertBox("Failed to update record: " + (response.message || 'Unknown error'), false);
        }
    }).catch(() => alertBox("Error occurred during update.", false));
}

function deleteRow(mrNumber, button) {
    if (!confirm("Are you sure you want to delete this patient?")) return;

    fetch('common_update_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'delete',
            table: 'gynae_patients',
            key: 'mr_number',
            value: mrNumber
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
</script>
<?php include('../includes/footer.php'); ?>
</body>
</html>
