<?php

function requireRole($allowedRoles) {
    if (!isLoggedIn()) {
        redirect('../login.php');
    }

    $userRole = $_SESSION['role'] ?? '';

    if (!in_array($userRole, $allowedRoles)) {
        // Simple unauthorized message or redirect
        die("Unauthorized Access. Role: " . htmlspecialchars($userRole));
    }
}


function requireAdmin() {
    // ONLY 'admin' role. PE Admin is notoriously excluded here.
    requireRole(['admin']);
}

function requireRegister() {
    requireRole(['register', 'admin']); 
}

function requireConferenceAccess() {
    // Students, Teachers, Staff, Admin, Reigster. 
    // PE Admin is NOT allowed in conference area unless we want them to? 
    // Requirement says "He wont see or have access of other admin panels". 
    // Asking for clarification might be good, but safe bet is restrict.
    // However, if PE Admin is an employee, maybe they can book? 
    // For now, let's Stick to the requirement: "He will be only able to see field booking admin panel"
    requireRole(['student', 'teacher', 'staff', 'admin', 'register']); 
}

function requireGuestHouseAccess() {
    // Teachers, Staff, Admin. STUDENTS NOT ALLOWED.
    requireRole(['teacher', 'staff', 'admin']);
}

function requireSportsAdmin() {
    // Admin OR PE Admin
    requireRole(['admin', 'pe_admin']);
}
?>
