<?php
require_once 'includes/db.php';

try {
    // 1. Create sports_fields table
    $sql1 = "CREATE TABLE IF NOT EXISTS sports_fields (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql1);
    echo "Table 'sports_fields' created successfully.<br>";

    // 2. Seed sports_fields
    // Check if empty first to avoid duplicate seeds
    $stmt = $pdo->query("SELECT count(*) FROM sports_fields");
    if ($stmt->fetchColumn() == 0) {
        $sql2 = "INSERT INTO sports_fields (name, location) VALUES 
            ('Central Field', 'Main Campus Center'),
            ('Mukto Moncho Field', 'Near Auditorium'),
            ('Balir Math', 'East Side Campus')";
        $pdo->exec($sql2);
        echo "Sports fields seeded.<br>";
    } else {
        echo "Sports fields already seeded.<br>";
    }

    // 3. Create sports_bookings table
    $sql3 = "CREATE TABLE IF NOT EXISTS sports_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        field_id INT,
        booking_date DATE NOT NULL,
        slot ENUM('morning', 'afternoon') NOT NULL,
        purpose VARCHAR(255),
        team_name VARCHAR(255),
        participants INT,
        notes TEXT,
        status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
        rejection_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (field_id) REFERENCES sports_fields(id)
    )";
    $pdo->exec($sql3);
    echo "Table 'sports_bookings' created successfully.<br>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
