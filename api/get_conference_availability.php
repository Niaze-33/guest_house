<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
if (!$roomId) {
    jsonResponse(false, 'Room ID required');
}

// Range: Today to +10 days
$today = new DateTime();
$endDate = new DateTime('+10 days');

// Fetch approved bookings for this room in range
$stmt = $pdo->prepare("
    SELECT start_time, end_time 
    FROM conference_bookings 
    WHERE conference_room_id = ? 
    AND status = 'approved'
    AND start_time >= ? 
    AND end_time <= ?
");
$stmt->execute([
    $roomId, 
    $today->format('Y-m-d 00:00:00'), 
    $endDate->format('Y-m-d 23:59:59')
]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize 10 days structure
$availability = [];
$cursor = clone $today;

for ($i = 0; $i < 10; $i++) {
    $dateStr = $cursor->format('Y-m-d');
    $availability[$dateStr] = [
        'morning' => true,   // true = available
        'afternoon' => true
    ];
    $cursor->modify('+1 day');
}

// Process bookings to mark unavailability
foreach ($bookings as $b) {
    $start = new DateTime($b['start_time']);
    $end = new DateTime($b['end_time']);
    $dateStr = $start->format('Y-m-d');

    // Need to check specific times to identify shift
    // Morning: 09-12, Afternoon: 14-17. 
    // If booking covers 09:00 (start) to 12:00, it blocks morning.
    // If booking covers 14:00 to 17:00, it blocks afternoon.
    // If start is 09:00 and end is 17:00, it blocks both.
    
    $sHour = (int)$start->format('H');
    $eHour = (int)$end->format('H');

    if (isset($availability[$dateStr])) {
        // Simple logic: 
        // Overlap with Morning (09-12)?
        // If booking starts < 12 and ends > 09
        if ($sHour < 12 && $eHour > 9) {
            $availability[$dateStr]['morning'] = false;
        }

        // Overlap with Afternoon (14-17)?
        // If booking starts < 17 and ends > 14
        if ($sHour < 17 && $eHour > 14) {
            $availability[$dateStr]['afternoon'] = false;
        }
    }
}

jsonResponse(true, 'Availability fetched', ['availability' => $availability]);
?>
