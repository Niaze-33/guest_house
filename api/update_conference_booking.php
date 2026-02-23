<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../middleware/role_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Only 'register' or 'admin' can approve/reject conference requests
requireRole(['register', 'admin']);

$data = json_decode(file_get_contents('php://input'), true);

$bookingId = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
$status = sanitize($data['status'] ?? '');

if (!$bookingId || !in_array($status, ['approved', 'rejected'])) {
    jsonResponse(false, 'Invalid data');
}

try {
    // If approving, check for overlap again just to be safe (race condition)
    if ($status === 'approved') {
        $stmtBooking = $pdo->prepare("SELECT conference_room_id, start_time, end_time FROM conference_bookings WHERE id = ?");
        $stmtBooking->execute([$bookingId]);
        $booking = $stmtBooking->fetch();
        
        if (!$booking) {
            jsonResponse(false, 'Booking not found');
        }

        $stmtOverlap = $pdo->prepare("
            SELECT COUNT(*) FROM conference_bookings 
            WHERE conference_room_id = ? 
            AND status = 'approved'
            AND id != ?
            AND start_time < ? AND end_time > ?
        ");
        $stmtOverlap->execute([
            $booking['conference_room_id'], 
            $bookingId,
            $booking['end_time'], 
            $booking['start_time']
        ]);
        
        if ($stmtOverlap->fetchColumn() > 0) {
            jsonResponse(false, 'Cannot approve: Room slot is already overlapped by another approved booking.');
        }
    }

    $stmt = $pdo->prepare("UPDATE conference_bookings SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $bookingId])) {
        jsonResponse(true, 'Booking ' . $status . ' successfully');
    } else {
        jsonResponse(false, 'Failed to update booking');
    }

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>
