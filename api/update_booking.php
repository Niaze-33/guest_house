<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    jsonResponse(false, 'Unauthorized');
}

$data = json_decode(file_get_contents('php://input'), true);

$bookingId = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
$status = sanitize($data['status'] ?? '');

if (!$bookingId || !in_array($status, ['approved', 'rejected'])) {
    jsonResponse(false, 'Invalid data');
}

try {
    $pdo->beginTransaction();

    // Update Booking Status
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$status, $bookingId]);

    if ($status === 'approved') {
        // If approved, mark the beds as booked
        // 1. Get beds associated with booking
        $stmtBeds = $pdo->prepare("SELECT bed_id FROM booking_beds WHERE booking_id = ?");
        $stmtBeds->execute([$bookingId]);
        $bedIds = $stmtBeds->fetchAll(PDO::FETCH_COLUMN);

        // 2. Get user gender
        $stmtUser = $pdo->prepare("SELECT gender FROM users WHERE id = (SELECT user_id FROM bookings WHERE id = ?)");
        $stmtUser->execute([$bookingId]);
        $userGender = $stmtUser->fetchColumn();

        if ($bedIds) {
            $placeholders = implode(',', array_fill(0, count($bedIds), '?'));
            $params = array_merge([$userGender], $bedIds);
            
            $sql = "UPDATE beds SET is_booked = 1, booked_by_gender = ? WHERE id IN ($placeholders)";
            $stmtUpdateBeds = $pdo->prepare($sql);
            $stmtUpdateBeds->execute($params);
        }
    } elseif ($status === 'rejected') {
        // Validation/Logic for rejection if needed
        // Assuming we don't need to free beds because they weren't marked booked yet (only on approval)
    }

    $pdo->commit();
    jsonResponse(true, 'Booking updated');

} catch (PDOException $e) {
    $pdo->rollBack();
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>
