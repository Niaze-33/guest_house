<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

// Fetch all bookings
$stmt = $pdo->query("
    SELECT b.*, u.full_name, u.email, gh.name as gh_name, r.room_number,
    GROUP_CONCAT(bd.bed_number SEPARATOR ', ') as bed_numbers
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    JOIN guest_houses gh ON r.guest_house_id = gh.id
    LEFT JOIN booking_beds bb ON b.id = bb.booking_id
    LEFT JOIN beds bd ON bb.bed_id = bd.id
    WHERE b.status IN ('pending', 'approved', 'rejected') -- Hide checked_out by default
    GROUP BY b.id
    ORDER BY b.submitted_at DESC
");
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Bookings - University Guest House</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: hsl(222.2 47.4% 11.2%);
            --color-primary-foreground: hsl(210 40% 98%);
            --color-background: hsl(0 0% 100%);
            --color-foreground: hsl(222.2 47.4% 11.2%);
            --color-muted: hsl(210 40% 96.1%);
            --color-success: hsl(142.1 76.2% 36.3%);
            --color-destructive: hsl(0 84.2% 60.2%);
        }
    </style>
</head>
<body class="bg-background text-foreground font-sans">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Sidebar -->
        <aside class="w-full md:w-64 bg-muted border-r border-border min-h-screen p-6">
            <h1 class="text-xl font-bold mb-8">Admin Panel</h1>
            <nav class="space-y-2">
                <a href="index.php" class="block px-4 py-2 rounded-md hover:bg-white/50">Overview</a>
                <a href="bookings.php" class="block px-4 py-2 rounded-md bg-white shadow-sm font-medium">Bookings</a>
                <a href="rooms.php" class="block px-4 py-2 rounded-md hover:bg-white/50">Rooms</a>
                <a href="../logout.php" class="block px-4 py-2 rounded-md text-red-600 hover:bg-red-50 mt-8">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold">Booking Requests</h2>
            </div>

            <div class="space-y-4">
                <?php if (empty($bookings)): ?>
                    <p class="text-muted-foreground">No bookings found.</p>
                <?php endif; ?>

                <?php foreach ($bookings as $booking): ?>
                <div class="bg-white rounded-lg border shadow-sm p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($booking['full_name']); ?></h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium 
                                <?php 
                                    if ($booking['status'] === 'approved') echo 'bg-green-100 text-green-700';
                                    elseif ($booking['status'] === 'rejected') echo 'bg-red-100 text-red-700';
                                    else echo 'bg-yellow-100 text-yellow-700'; 
                                ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            <?php echo htmlspecialchars($booking['gh_name']); ?> • Room <?php echo htmlspecialchars($booking['room_number']); ?>
                            <span class="ml-1 text-gray-500">| Bed: <strong><?php echo htmlspecialchars($booking['bed_numbers']); ?></strong></span>
                        </p>
                        <p class="text-xs text-muted-foreground">
                            <?php echo date('M d, Y', strtotime($booking['check_in'])); ?> - <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                        </p>
                    </div>

                    <?php if ($booking['status'] === 'pending'): ?>
                    <div class="flex items-center gap-2">
                        <button onclick="updateStatus(<?php echo $booking['id']; ?>, 'approved')" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700 transition">
                            Approve
                        </button>
                        <button onclick="updateStatus(<?php echo $booking['id']; ?>, 'rejected')" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded hover:bg-red-700 transition">
                            Reject
                        </button>
                    </div>
                    <?php elseif ($booking['status'] === 'approved'): ?>
                         <button onclick="checkout(<?php echo $booking['id']; ?>)" class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition">
                            Force Check Out
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
        async function updateStatus(bookingId, status) {
            if (!confirm(`Are you sure you want to ${status} this booking?`)) return;

            try {
                const response = await fetch('../api/update_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId, status: status })
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast(`Booking ${status} successfully`, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Error updating booking', 'error');
            }
        }

        async function checkout(bookingId) {
            if (!confirm('Checkout this user?')) return;
            try {
                const response = await fetch('../api/checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId })
                });
                const res = await response.json();
                if (res.success) {
                    showToast('User checked out', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(res.message, 'error');
                }
            } catch (err) { 
                showToast('Error processing checkout', 'error');
            }
        }
    </script>
</body>
</html>
