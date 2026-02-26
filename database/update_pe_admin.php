<?php
require_once 'includes/db.php';

try {
    // 1. Alter users table to add 'pe_admin' to ENUM
    // Note: modifying ENUMs in MySQL can be tricky. We'll use a raw query.
    // Current ENUM: 'admin', 'register', 'teacher', 'staff', 'student'
    
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'register', 'teacher', 'staff', 'student', 'pe_admin') DEFAULT 'student'";
    $pdo->exec($sql);
    echo "Users table updated to include 'pe_admin' role.<br>";

    // 2. Insert PE Admin User
    $email = 'peadmin@varsityhub.edu';
    $password = password_hash('123456', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if (!$stmt->fetch()) {
        $stmtInsert = $pdo->prepare("INSERT INTO users (full_name, email, password, role, gender) VALUES (?, ?, ?, 'pe_admin', 'male')");
        $stmtInsert->execute(['PE Admin', $email, $password]);
        echo "PE Admin user created (Email: $email, Pass: 123456)<br>";
    } else {
        echo "PE Admin user already exists.<br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
