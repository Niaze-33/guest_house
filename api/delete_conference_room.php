<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!in_array($_SESSION['role'], ['register', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['room_id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
    exit;
}

try {
    // Check if there are any bookings for this room
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM conference_bookings WHERE conference_room_id = ?");
    $stmtCheck->execute([$id]);
    if ($stmtCheck->fetchColumn() > 0) {
         // Optionally deletion could be blocked or cascading. 
         // For now, let's allow it but warn about potential data loss if foreign keys are not cascading.
    }

    $stmt = $pdo->prepare("DELETE FROM conference_rooms WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Conference room deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Room not found or already deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
