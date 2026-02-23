<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);

$fullName = sanitize($data['fullName'] ?? '');
$email = sanitize($data['email'] ?? '');
$gender = sanitize($data['gender'] ?? '');
$designation = sanitize($data['designation'] ?? '');
$department = sanitize($data['department'] ?? '');
$role = sanitize($data['role'] ?? 'student');
$password = $data['password'] ?? '';

// Validate Role
$allowedRoles = ['admin', 'register', 'student', 'teacher', 'staff'];
if (!in_array($role, $allowedRoles)) {
    jsonResponse(false, 'Invalid role selected');
}

// Basic server-side validation
if (empty($fullName) || empty($email) || empty($password)) {
    jsonResponse(false, 'All fields are required');
}

try {
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Email already registered');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, gender, designation, department, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullName, $email, $hashedPassword, $gender, $designation, $department, $role]);

    // Auto login after registration
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['role'] = $role;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['gender'] = $gender;

    // Determine redirect based on role
    // Determine redirect based on role
    $redirectUrl = 'index.php'; // Redirect to homepage as requested

    jsonResponse(true, 'Registration successful', ['redirect' => $redirectUrl]);

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>
