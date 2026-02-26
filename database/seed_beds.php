<?php
require_once 'includes/db.php';

echo "Start Seeding...<br>";

$stmt = $pdo->query("SELECT * FROM rooms");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;

foreach ($rooms as $room) {
    // Check existing beds
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM beds WHERE room_id = ?");
    $stmtCheck->execute([$room['id']]);
    if ($stmtCheck->fetchColumn() > 0) {
        continue; // Already has beds
    }

    // Determine bed count
    $bedCount = 1;
    if (stripos($room['type'], 'Double') !== false) $bedCount = 2;
    if (stripos($room['type'], 'Triple') !== false) $bedCount = 3;

    // Insert Beds
    $letters = range('A', 'Z');
    for ($i = 0; $i < $bedCount; $i++) {
        $bedNum = $letters[$i];
        $stmtInsert = $pdo->prepare("INSERT INTO beds (room_id, bed_number) VALUES (?, ?)");
        $stmtInsert->execute([$room['id'], $bedNum]);
        $count++;
    }
}

echo "Done! Seeded $count new beds.";
?>
