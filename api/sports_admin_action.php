<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// session_start(); // Already started in functions.php
// Allow PE Admin ONLY
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pe_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only PE Admin can perform this action.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bookingId = intval($input['booking_id'] ?? 0);
$action = $input['action'] ?? ''; // 'approve' or 'reject'
$reason = sanitize($input['reason'] ?? '');

if (!$bookingId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action or booking ID']);
    exit;
}

try {
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
    
    $stmt = $pdo->prepare("UPDATE sports_bookings SET status = ?, rejection_reason = ? WHERE id = ?");
    $stmt->execute([$newStatus, $reason, $bookingId]);

    echo json_encode(['success' => true, 'message' => "Booking $newStatus successfully"]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
