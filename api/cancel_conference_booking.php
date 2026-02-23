<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

if (!isLoggedIn()) {
    jsonResponse(false, 'Unauthorized');
}

$data = json_decode(file_get_contents('php://input'), true);
$bookingId = $data['booking_id'] ?? 0;
$userId = $_SESSION['user_id'];

if (!$bookingId) {
    jsonResponse(false, 'Invalid booking ID');
}

try {
    // 1. Verify Ownership & Status
    $stmt = $pdo->prepare("SELECT status FROM conference_bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$bookingId, $userId]);
    $currentStatus = $stmt->fetchColumn();

    if (!$currentStatus) {
        jsonResponse(false, 'Booking not found or access denied');
    }

    if (!in_array($currentStatus, ['pending', 'approved'])) {
        jsonResponse(false, 'Cannot cancel booking. Current status is ' . $currentStatus);
    }

    // 2. Update Status to 'rejected' (Acting as Cancelled)
    // We use 'rejected' because schema ENUM doesn't have 'cancelled' and this frees the slot.
    $stmtUpdate = $pdo->prepare("UPDATE conference_bookings SET status = 'rejected' WHERE id = ?");
    
    if ($stmtUpdate->execute([$bookingId])) {
        jsonResponse(true, 'Booking cancelled successfully');
    } else {
        jsonResponse(false, 'Failed to cancel booking');
    }

} catch (Exception $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>
