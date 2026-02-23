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
    // Check if booking exists, belongs to user, and is pending
    $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$bookingId, $userId]);
    $status = $stmt->fetchColumn();

    if (!$status) {
        jsonResponse(false, 'Booking not found or access denied');
    }

    if ($status !== 'pending') {
        jsonResponse(false, 'Cannot cancel booking. Status is ' . $status);
    }

    // Delete or Update status? 
    // Usually 'cancelled' status is better for history. But if table doesn't have it, we might delete.
    // The schema enum is 'pending', 'approved', 'rejected', 'checked_out'. 
    // If we want to support cancellation, we should probably add 'cancelled' to ENUM or just DELETE if it's pending.
    // The prompt says "cancel his booking request".
    // If I look at the schema: ENUM('pending', 'approved', 'rejected', 'checked_out').
    // I will try to DELETE strictly for 'pending' requests, as they are just requests.
    // OR, I can alter the table. Altering table is safer. But DELETE is cleaner for "requests" that never happened.
    // Let's go with DELETE for now for pending requests.

    $stmtDelete = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmtDelete->execute([$bookingId]);

    // Cleanup booking_beds? Cascade delete handles it?
    // Schema says: FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    // So yes, it handles it.

    jsonResponse(true, 'Booking cancelled successfully');

} catch (Exception $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>
