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
$bookingId = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if (!$bookingId) {
    jsonResponse(false, 'Booking ID required');
}

try {
    // 1. Verify Booking ownership or Admin access
    $stmt = $pdo->prepare("SELECT user_id, status FROM bookings WHERE id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        jsonResponse(false, 'Booking not found');
    }

    if (!$isAdmin && $booking['user_id'] !== $_SESSION['user_id']) {
        jsonResponse(false, 'You do not have permission to modify this booking');
    }

    if ($booking['status'] !== 'approved') {
        jsonResponse(false, 'Only active (approved) bookings can be checked out');
    }

    // 2. Perform Checkout
    $pdo->beginTransaction();

    // Update Status
    $stmtUpdate = $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
    $stmtUpdate->execute([$bookingId]);

    // Free up beds (if we were using is_booked flag, which we are somewhat using visually)
    // Even though our new search uses date-overlaps, it is good hygiene to reset the 'is_booked' flag
    // so legacy or simple views still show them as free if they check simple flags.
    $stmtBeds = $pdo->prepare("SELECT bed_id FROM booking_beds WHERE booking_id = ?");
    $stmtBeds->execute([$bookingId]);
    $bedIds = $stmtBeds->fetchAll(PDO::FETCH_COLUMN);

    if ($bedIds) {
        $placeholders = implode(',', array_fill(0, count($bedIds), '?'));
        $stmtFree = $pdo->prepare("UPDATE beds SET is_booked = 0, booked_by_gender = NULL WHERE id IN ($placeholders)");
        $stmtFree->execute($bedIds);
    }

    $pdo->commit();
    jsonResponse(true, 'Checked out successfully');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>
