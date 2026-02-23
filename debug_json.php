<?php
header('Content-Type: application/json');
require_once 'includes/db.php';
$houseId = $_GET['id'] ?? 1;
$res = [];
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE guest_house_id = ?");
$stmt->execute([$houseId]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rooms as $room) {
    $r = $room;
    $stmtBeds = $pdo->prepare("SELECT * FROM beds WHERE room_id = ?");
    $stmtBeds->execute([$room['id']]);
    $r['beds'] = $stmtBeds->fetchAll(PDO::FETCH_ASSOC);
    $res[] = $r;
}
echo json_encode($res, JSON_PRETTY_PRINT);
?>
