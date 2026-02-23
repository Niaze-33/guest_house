<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// session_start(); // Already started in functions.php
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bookingId = intval($input['booking_id'] ?? 0);
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

if (!$bookingId) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id, status FROM sports_bookings WHERE id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // Authorization check
    // User can cancel if it's their booking AND status is pending
    // Admin can cancel anytime
    
    if ($userRole === 'admin' || $userRole === 'pe_admin') {
        // Admin can cancel
    } elseif ($booking['user_id'] == $userId) {
        if ($booking['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'You can only cancel pending requests. Contact admin for approved bookings.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized action']);
        exit;
    }

    $updateStmt = $pdo->prepare("UPDATE sports_bookings SET status = 'cancelled' WHERE id = ?");
    $updateStmt->execute([$bookingId]);

    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
