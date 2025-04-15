<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
include('../includes/sidebar.php');
hasRole('children');

$baby_mr = $_GET['baby_mr'] ?? '';
$baby_name = $_GET['baby_name'] ?? '';
$opd_date = $_GET['opd_date'] ?? '';

$query = "SELECT * FROM newborns";
$conditions = [];
$params = [];
$types = '';

if (!empty($baby_mr)) {
    $conditions[] = "baby_mr LIKE ?";
    $params[] = "%" . $baby_mr . "%";
    $types .= 's';
}
if (!empty($baby_name)) {
    $conditions[] = "baby_name LIKE ?";
    $params[] = "%" . $baby_name . "%";
    $types .= 's';
}
if (!empty($opd_date)) {
    $conditions[] = "opd_date = ?";
    $params[] = $opd_date;
    $types .= 's';
}

$sort_order = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) as total FROM newborns";
if (!empty($conditions)) {
    $count_query .= " WHERE " . implode(" OR ", $conditions);
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];

    $query .= " WHERE " . implode(" OR ", $conditions) . " ORDER BY created_at $sort_order LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $total_result = $conn->query("SELECT COUNT(*) as total FROM newborns");
    $total_records = $total_result->fetch_assoc()['total'];
    $result = $conn->query("SELECT * FROM newborns ORDER BY created_at $sort_order LIMIT $limit OFFSET $offset");
}

$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Children Hospital - Newborn List</title>
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
            margin-right: 5px;
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
        <h2>Newborn List</h2>
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" name="baby_mr" class="form-control" placeholder="Baby MR Number" value="<?= htmlspecialchars($_GET['baby_mr'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="baby_name" class="form-control" placeholder="Baby Name" value="<?= htmlspecialchars($_GET['baby_name'] ?? '') ?>">
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
                    <input type="hidden" name="baby_mr" value="<?= htmlspecialchars($_GET['baby_mr'] ?? '') ?>">
                    <input type="hidden" name="baby_name" value="<?= htmlspecialchars($_GET['baby_name'] ?? '') ?>">
                    <input type="hidden" name="opd_date" value="<?= htmlspecialchars($_GET['opd_date'] ?? '') ?>">
                    <label for="sort" class="me-2 fw-semibold">Sort By:</label>
                    <select class="form-select form-select-sm" name="sort" id="sort" onchange="this.form.submit()" style="max-width: 200px;">
                        <option value="desc" <?= ($_GET['sort'] ?? '') === 'desc' ? 'selected' : '' ?>>Newest First</option>
                        <option value="asc" <?= ($_GET['sort'] ?? '') === 'asc' ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </form>
            </div>
        </div>

        <table class="table table-bordered table-hover" style="min-width: 1400px;">
            <thead class="table-light">
                <tr>
                    <th>Baby MR</th>
                    <th>OPD No</th>
                    <th>Mother MR</th>
                    <th>Baby Name</th>
                    <th>DOB</th>
                    <th>Age</th>
                    <th>Address</th>
                    <th>Mobile</th>
                    <th>Sex</th>
                    <th>Category</th>
                    <th>OPD Date</th>
                    <th>OPD Time</th>
                    <th>Weight</th>
                    <th>Height</th>
                    <th>Department</th>
                    <th>Doctor</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <?php
                            $fields = ['baby_mr', 'baby_opd_no', 'mother_mr', 'baby_name', 'dob', 'age', 'address', 'mobile', 'sex', 'category', 'opd_date', 'opd_time', 'weight', 'height', 'department', 'doctor'];
                            foreach ($fields as $field) {
                                echo "<td>" . htmlspecialchars($row[$field] ?? '') . "</td>";
                            }
                            ?>
                            <td>
                                <button class="btn btn-edit" onclick="makeRowEditable(this)">Edit</button>
                                <button class="btn btn-delete" onclick="deleteRow('<?= $row['baby_mr'] ?>', this)">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="17" class="text-center">No newborn records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

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
    const babyMr = row.children[0].innerText.trim();

    const fieldTypes = {
        4: 'date',  // dob
        10: 'date', // opd_date
        11: 'time'  // opd_time
    };

    for (let i = 1; i <= 15; i++) {
        const cell = row.children[i];
        if (cell.querySelector('input')) continue; // Skip if already in edit mode
        const val = cell.innerText.trim();
        const input = document.createElement('input');
        input.type = fieldTypes[i] || 'text';
        input.className = 'form-control form-control-sm';
        input.value = val;

        if (i === 4) { // dob
            input.onchange = () => {
                const dob = new Date(input.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                row.children[5].innerText = age;
            };
        }

        cell.innerHTML = '';
        cell.appendChild(input);
    }

    button.textContent = 'Save';
    button.onclick = () => saveRow(row, babyMr, button);
}

function saveRow(row, babyMr, button) {
    const fields = ['baby_opd_no', 'mother_mr', 'baby_name', 'dob', 'age', 'address', 'mobile', 'sex', 'category', 'opd_date', 'opd_time', 'weight', 'height', 'department', 'doctor'];
    const data = {
        action: 'update',
        table: 'newborns',
        key: 'baby_mr',
        value: babyMr,
        fields: {}
    };

    for (let i = 0; i < fields.length; i++) {
        const input = row.children[i + 1].querySelector('input');
        const val = input ? input.value.trim() : '';
        data.fields[fields[i]] = val === '' ? null : val;
    }

    fetch('common_update_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(resp => resp.json())
    .then(response => {
        if (response.success) {
            for (let i = 0; i < fields.length; i++) {
                const cell = row.children[i + 1];
                cell.innerHTML = data.fields[fields[i]] ?? '';
            }
            button.textContent = 'Edit';
            button.onclick = () => makeRowEditable(button);
            alertBox("Record updated successfully.");
        } else {
            alertBox("Failed to update record: " + (response.message || 'Unknown error'), false);
        }
    }).catch(() => alertBox("Error occurred during update.", false));
}

function deleteRow(babyMr, button) {
    if (!confirm("Are you sure you want to delete this newborn record?")) return;

    fetch('common_update_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'delete',
            table: 'newborns',
            key: 'baby_mr',
            value: babyMr
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
