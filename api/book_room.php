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

$userId = $_SESSION['user_id'];
$userGender = $_SESSION['gender'] ?? 'male'; // Default/Fallback
$bedId = isset($data['bed_id']) ? intval($data['bed_id']) : 0;
$roomId = isset($data['room_id']) ? intval($data['room_id']) : 0;
// We ignore check_in/check_out logic overlapping for MVP as requested, relying on "Occupied" state.
// But we still save them.
$checkIn = sanitize($data['check_in'] ?? '');
$checkOut = sanitize($data['check_out'] ?? '');

if (!$bedId || !$roomId || !$checkIn || !$checkOut) {
    jsonResponse(false, 'Missing required fields');
}

try {
    // 1. Check if Bed is already Occupied (Pending OR Approved)
    // We check if it is in any booking that is NOT rejected.
    $stmtCheckBed = $pdo->prepare("
        SELECT COUNT(*) FROM booking_beds bb
        JOIN bookings b ON bb.booking_id = b.id
        WHERE bb.bed_id = ? AND b.status IN ('pending', 'approved')
    ");
    $stmtCheckBed->execute([$bedId]);
    if ($stmtCheckBed->fetchColumn() > 0) {
        jsonResponse(false, 'This bed is already pending approval or booked.');
    }

     
    // Find active bookings in this room (Pending OR Approved)
    $stmtCheckRoom = $pdo->prepare("
        SELECT u.gender FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.room_id = ? AND b.status IN ('pending', 'approved')
        LIMIT 1
    ");
    $stmtCheckRoom->execute([$roomId]);
    $existingGender = $stmtCheckRoom->fetchColumn();

    if ($existingGender && $existingGender !== $userGender) {
        jsonResponse(false, "This room is currently occupied by $existingGender guests. You cannot book a bed here.");
    }

    $pdo->beginTransaction();

    // Create Booking
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $roomId, $checkIn, $checkOut]);
    $bookingId = $pdo->lastInsertId();

    // Link Bed
    $stmtBed = $pdo->prepare("INSERT INTO booking_beds (booking_id, bed_id) VALUES (?, ?)");
    $stmtBed->execute([$bookingId, $bedId]);

    $pdo->commit();
    jsonResponse(true, 'Booking request submitted');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>
