<?php
require_once('../includes/auth.php');
require_once('../config/db.php');

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['action']) || !in_array($data['action'], ['update', 'delete'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    if (empty($data['table']) || empty($data['key']) || empty($data['value'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    $table = $data['table'];
    $key = $data['key'];
    $value = $data['value'];

    if ($data['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$key` = ?");
        $stmt->bind_param('s', $value);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Deletion failed']);
        }
        exit;
    }

    if ($data['action'] === 'update') {
        if (!isset($data['fields']) || !is_array($data['fields'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid fields']);
            exit;
        }

        $fields = $data['fields'];
        $updates = [];
        $params = [];
        $types = '';

        foreach ($fields as $col => $val) {
            $updates[] = "$col = ?";
            $params[] = $val;
            $types .= 's';
        }

        $params[] = $value;
        $types .= 's';

        $sql = "UPDATE `$table` SET " . implode(', ', $updates) . " WHERE `$key` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}