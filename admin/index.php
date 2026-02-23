<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Strict Admin check. PE Admin NOT allowed.
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    // If logged in as pe_admin, redirect to their dashboard
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'pe_admin') {
        header('Location: ../sports/admin_dashboard.php');
        exit;
    }
    header('Location: ../index.php');
    exit;
}

// Stats
$stats = [
    'pending_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'approved_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'approved'")->fetchColumn(),
    'total_rooms' => $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Guest House</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: hsl(222.2 47.4% 11.2%);
            --color-primary-foreground: hsl(210 40% 98%);
            --color-background: hsl(0 0% 100%);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-900">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Sidebar -->
        <aside class="w-full md:w-64 bg-white border-r border-gray-200 min-h-screen p-6">
            <h1 class="text-xl font-bold mb-8 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                Admin Panel
            </h1>
            <nav class="space-y-2">
                <a href="index.php" class="block px-4 py-2 rounded-md bg-blue-50 text-blue-700 font-medium">Overview</a>
                <a href="bookings.php" class="block px-4 py-2 rounded-md hover:bg-gray-50 text-gray-700">Bookings</a>
                <a href="rooms.php" class="block px-4 py-2 rounded-md hover:bg-gray-50 text-gray-700">Rooms</a>
                <a href="../logout.php" class="block px-4 py-2 rounded-md text-red-600 hover:bg-red-50 mt-8">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Dashboard Overview</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500">Pending Bookings</h3>
                    <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo $stats['pending_bookings']; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500">Approved Bookings</h3>
                    <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $stats['approved_bookings']; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500">Total Rooms</h3>
                    <p class="text-3xl font-bold text-blue-900 mt-2"><?php echo $stats['total_rooms']; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500">Registered Users</h3>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $stats['users']; ?></p>
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-xl font-semibold mb-4">Quick Actions</h3>
                <div class="flex gap-4">
                    <a href="bookings.php" class="bg-blue-900 text-white px-4 py-2 rounded-md font-medium hover:bg-blue-800">Review Bookings</a>
                    <a href="rooms.php" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md font-medium hover:bg-gray-50">Manage Rooms</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
