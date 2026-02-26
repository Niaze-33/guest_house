<?php
require_once 'includes/db.php';

$houseId = $_GET['id'] ?? 1;

echo "<h1>Debug Info for House ID: $houseId</h1>";

// Check Rooms
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE guest_house_id = ?");
$stmt->execute([$houseId]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Rooms Found: " . count($rooms) . "</h2>";
echo "<table border=1><tr><th>ID</th><th>Number</th><th>Status</th><th>Beds Count</th></tr>";

foreach ($rooms as $room) {
    // Check Beds
    $stmtBeds = $pdo->prepare("SELECT * FROM beds WHERE room_id = ?");
    $stmtBeds->execute([$room['id']]);
    $beds = $stmtBeds->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<tr>";
    echo "<td>" . $room['id'] . "</td>";
    echo "<td>" . htmlspecialchars($room['room_number']) . "</td>";
    echo "<td>" . htmlspecialchars($room['status']) . "</td>";
    echo "<td>" . count($beds) . " (IDs: " . implode(', ', array_column($beds, 'id')) . ")</td>";
    echo "</tr>";
}
echo "</table>";
?>
