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

$userId = $_SESSION['user_id'];
$fieldId = $input['field_id'] ?? null;
$slots = $input['slots'] ?? []; // Array of {date, slot}
$purpose = sanitize($input['purpose'] ?? '');
$teamName = sanitize($input['team_name'] ?? '');
$participants = intval($input['participants'] ?? 0);
$notes = sanitize($input['notes'] ?? '');

if (!$fieldId || empty($slots) || !$purpose) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM sports_bookings WHERE field_id = ? AND booking_date = ? AND slot = ? AND status IN ('approved', 'pending')");
    $stmtInsert = $pdo->prepare("INSERT INTO sports_bookings (user_id, field_id, booking_date, slot, purpose, team_name, participants, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

    foreach ($slots as $slotData) {
        $date = $slotData['date'];
        $slotName = $slotData['slot']; // 'morning' or 'afternoon'

        // Double check availability
        $stmtCheck->execute([$fieldId, $date, $slotName]);
        if ($stmtCheck->fetchColumn() > 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => "Slot $date ($slotName) is no longer available."]);
            exit;
        }

        $stmtInsert->execute([$userId, $fieldId, $date, $slotName, $purpose, $teamName, $participants, $notes]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Booking request submitted successfully!']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
