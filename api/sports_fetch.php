<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // 1. Fetch Fields
    $stmt = $pdo->query("SELECT * FROM sports_fields ORDER BY name ASC");
    $fields = $stmt->fetchAll();

    // 2. Fetch Availability if date range is provided (optional, or just return existing bookings)
    // For the availability table, we need to know which slots are booked for the next 10 days.
    
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+10 days'));

    $stmtBookings = $pdo->prepare("
        SELECT sb.field_id, sb.booking_date, sb.slot, sb.status, sb.purpose, sb.team_name, u.full_name as requester_name
        FROM sports_bookings sb
        JOIN users u ON sb.user_id = u.id
        WHERE sb.booking_date BETWEEN ? AND ? 
        AND sb.status IN ('approved', 'pending')
    ");
    $stmtBookings->execute([$startDate, $endDate]);
    $bookings = $stmtBookings->fetchAll();

    // Organize bookings by field_id -> date -> slot
    $availability = [];
    foreach ($bookings as $b) {
        $fid = $b['field_id'];
        $date = $b['booking_date'];
        $slot = $b['slot'];
        
        if (!isset($availability[$fid])) $availability[$fid] = [];
        if (!isset($availability[$fid][$date])) $availability[$fid][$date] = [];
        
        $availability[$fid][$date][$slot] = [
            'status' => $b['status'],
            'purpose' => $b['purpose'],
            'team_name' => $b['team_name'],
            'requester' => $b['requester_name']
        ];
    }

    echo json_encode([
        'success' => true,
        'fields' => $fields,
        'bookings' => $availability
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
