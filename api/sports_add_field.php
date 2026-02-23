<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SESSION['role'] !== 'pe_admin') {
    echo json_encode(['success' => false, 'message' => 'PE Admin access required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$name = sanitize($data['name'] ?? '');
$location = sanitize($data['location'] ?? '');

if (empty($name) || empty($location)) {
    echo json_encode(['success' => false, 'message' => 'Field name and location are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO sports_fields (name, location) VALUES (?, ?)");
    $stmt->execute([$name, $location]);

    echo json_encode([
        'success' => true, 
        'message' => 'Field added successfully',
        'field_id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
