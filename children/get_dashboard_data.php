<?php
require_once('../includes/auth.php');
require_once('../config/db.php');
hasRole('children');

header('Content-Type: application/json');

function getCount($conn, $sql) {
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'];
}

function getSum($conn, $sql) {
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

$data = [
    'totalPatients' => getCount($conn, "SELECT COUNT(*) as count FROM patients"),
    'todayOPD' => getCount($conn, "SELECT COUNT(*) as count FROM patients WHERE DATE(opd_date) = CURDATE()"),
    'currentIPD' => getCount($conn, "SELECT COUNT(*) as count FROM ipd_records WHERE discharge_date IS NULL"),
    'totalDischarges' => getCount($conn, "SELECT COUNT(*) as count FROM discharge_summary"),
    'totalNewborns' => getCount($conn, "SELECT COUNT(*) as count FROM newborns"),
    'monthlyRevenue' => getSum($conn, "SELECT SUM(total_amount) as total FROM invoices WHERE MONTH(date) = MONTH(CURDATE())")
];

echo json_encode($data);
?>