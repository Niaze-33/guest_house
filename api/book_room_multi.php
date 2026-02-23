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

if (!isset($data) || !$data) {
    jsonResponse(false, 'Invalid JSON input');
}

// 2. Validate and Create Bookings
$bookingsToCreate = $data;

// If it's not a list of bookings (indexed array), wrap it or fail
if (!is_array($bookingsToCreate) || empty($bookingsToCreate) || isset($bookingsToCreate['bed_ids'])) {
     // Check if it's the old format passing a single object? No, we enforced array in JS.
     // But just in case, let's just validations inside the loop catch structure issues.
     // If it is an associative array (old format), we reject it.
     if (array_keys($bookingsToCreate) !== range(0, count($bookingsToCreate) - 1)) {
        jsonResponse(false, 'Invalid booking format. Expected a list of bookings.');
     }
     if (array_keys($bookingsToCreate) !== range(0, count($bookingsToCreate) - 1)) {
        jsonResponse(false, 'Invalid booking format. Expected a list of bookings.');
     }
}

$userId = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    foreach ($bookingsToCreate as $bookingReq) {
        $bedId = $bookingReq['bed_id'];
        $bCheckIn = sanitize($bookingReq['check_in']);
        $bCheckOut = sanitize($bookingReq['check_out']);

        if (!$bedId || !$bCheckIn || !$bCheckOut) {
            throw new Exception("Invalid booking data provided.");
        }

        // A. Validate Availability (Double Booking Check)
        $stmtCheck = $pdo->prepare("
            SELECT count(*) FROM booking_beds bb
            JOIN bookings b ON bb.booking_id = b.id
            WHERE bb.bed_id = ? 
            AND b.status IN ('pending', 'approved')
            AND (b.check_in < ? AND b.check_out > ?) 
        ");
        $stmtCheck->execute([$bedId, $bCheckOut, $bCheckIn]);
        
        if ($stmtCheck->fetchColumn() > 0) {
            // Get Bed Info for error message
            $stmtName = $pdo->prepare("SELECT bed_number FROM beds WHERE id = ?");
            $stmtName->execute([$bedId]);
            $bedNum = $stmtName->fetchColumn(); 
            throw new Exception("Bed $bedNum is is already booked for " . $bCheckIn);
        }

        // B. Create Booking
        // Note: In this new model, 1 Selected Range = 1 Booking Record. 
        // If user selects Mon-Wed, that's one booking. 
        $stmtBooking = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, status) VALUES (?, (SELECT room_id FROM beds WHERE id = ?), ?, ?, 'pending')");
        $stmtBooking->execute([$userId, $bedId, $bCheckIn, $bCheckOut]);
        $bookingId = $pdo->lastInsertId();

        // C. Link bed (Legacy compatibility or standard link)
        $stmtLink = $pdo->prepare("INSERT INTO booking_beds (booking_id, bed_id) VALUES (?, ?)");
        $stmtLink->execute([$bookingId, $bedId]);
    }

    $pdo->commit();
    jsonResponse(true, 'Booking request submitted');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, $e->getMessage());
}
?>
