<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

if (!isLoggedIn()) {
    jsonResponse(false, 'Unauthorized');
}

// Access Control: Students, Teachers, Staff, Admin, Register
// Role check is implicit via isLoggedIn + role logic in DB, but let's be strict if needed.
// Actually, any logged in user (with valid role) typically can request.
// Students ARE allowed to book conference rooms per requirements.

$data = json_decode(file_get_contents('php://input'), true);

$roomId = isset($data['room_id']) ? intval($data['room_id']) : 0;
// Expect 'bookings' array: [{date: 'Y-m-d', shift: 'morning'|'afternoon'}, ...]
$bookings = $data['bookings'] ?? [];
$userId = $_SESSION['user_id'];

if (!$roomId || empty($bookings)) {
    jsonResponse(false, 'Please select at least one slot.');
}

$pdo->beginTransaction();

try {
    $stmtOverlap = $pdo->prepare("
        SELECT COUNT(*) FROM conference_bookings 
        WHERE conference_room_id = ? 
        AND status = 'approved'
        AND start_time < ? AND end_time > ?
    ");

    $stmtInsert = $pdo->prepare("
        INSERT INTO conference_bookings (user_id, conference_room_id, start_time, end_time, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");

    $successCount = 0;

    foreach ($bookings as $slot) {
        $date = sanitize($slot['date']);
        $shift = sanitize($slot['shift']);
        
        $startTime = '';
        $endTime = '';

        if ($shift === 'morning') {
            $startTime = $date . ' 09:00:00';
            $endTime = $date . ' 12:00:00';
        } elseif ($shift === 'afternoon') {
            $startTime = $date . ' 14:00:00';
            $endTime = $date . ' 17:00:00';
        } else {
            continue; // Skip invalid
        }

        // Check overlap for THIS specific slot
        $stmtOverlap->execute([$roomId, $endTime, $startTime]);
        if ($stmtOverlap->fetchColumn() > 0) {
            // If any slot is taken, fail the whole batch? Or skip?
            // Usually simpler to fail batch or just verify in UI. 
            // Let's rollback and error.
            $pdo->rollBack();
            jsonResponse(false, "Slot $date ($shift) is no longer available.");
        }

        $stmtInsert->execute([$userId, $roomId, $startTime, $endTime]);
        $successCount++;
    }

    $pdo->commit();
    jsonResponse(true, "Successfully requested $successCount slots.");

} catch (PDOException $e) {
    $pdo->rollBack();
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>
