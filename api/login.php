<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);

$email = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(false, 'Email and password are required');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['gender'] = $user['gender'];
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            $redirectUrl = 'admin/index.php';
        } elseif ($user['role'] === 'pe_admin') {
            $redirectUrl = 'sports/admin_dashboard.php';
        } elseif ($user['role'] === 'register') {
            $redirectUrl = 'register/dashboard.php';
        } else {
            $redirectUrl = 'index.php';
        }
        
        jsonResponse(true, 'Login successful', ['redirect' => $redirectUrl]);
    } else {
        jsonResponse(false, 'Invalid email or password');
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>
