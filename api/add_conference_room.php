<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only 'register' and 'admin' roles can manage conference rooms
if (!in_array($_SESSION['role'], ['register', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$name = sanitize($data['name'] ?? '');
$location = sanitize($data['location'] ?? '');
$capacity = intval($data['capacity'] ?? 0);

if (empty($name) || empty($location) || $capacity <= 0) {
    echo json_encode(['success' => false, 'message' => 'All fields (Name, Location, Capacity) are required and capacity must be positive']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO conference_rooms (name, location, capacity) VALUES (?, ?, ?)");
    $stmt->execute([$name, $location, $capacity]);

    echo json_encode([
        'success' => true, 
        'message' => 'Conference room added successfully',
        'room_id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
