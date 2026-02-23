<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Fetch user bookings with Bed Details
$stmt = $pdo->prepare("
    SELECT b.*, gh.name as gh_name, r.room_number,
    GROUP_CONCAT(bd.bed_number SEPARATOR ', ') as bed_numbers
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN guest_houses gh ON r.guest_house_id = gh.id
    LEFT JOIN booking_beds bb ON b.id = bb.booking_id
    LEFT JOIN beds bd ON bb.bed_id = bd.id
    WHERE b.user_id = ?
    GROUP BY b.id
    ORDER BY b.submitted_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - University Guest House</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: hsl(222.2 47.4% 11.2%);
            --color-primary-foreground: hsl(210 40% 98%);
            --color-background: hsl(0 0% 100%);
            --color-foreground: hsl(222.2 47.4% 11.2%);
            --color-muted: hsl(210 40% 96.1%);
            --color-muted-foreground: hsl(215.4 16.3% 46.9%);
        }
    </style>
</head>
<body class="bg-background text-foreground font-sans">
    <div class="min-h-screen flex flex-col">
        <header class="bg-primary text-primary-foreground py-4 px-6 flex justify-between items-center shadow-md">
            <div class="flex items-center gap-4">
                <a href="index.php" class="flex items-center gap-2 hover:text-gray-200 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span class="font-medium">Home</span>
                </a>
                <span class="text-primary-foreground/30">|</span>
                <h1 class="text-xl font-bold">Dashboard</h1>
            </div>
            <div class="flex items-center gap-4">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="dashboard.php" class="text-sm font-medium hover:underline">Dashboard</a>
                <a href="logout.php" class="text-sm bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition">Logout</a>
            </div>
        </header>
        
        <main class="flex-1 p-6 md:p-10">
            <div class="max-w-4xl mx-auto space-y-8">
                
                <!-- Action Card -->
                <div class="bg-white p-6 rounded-lg border shadow-sm flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold">Ready to book a room?</h2>
                        <p class="text-muted-foreground">Browse our guest houses and find available rooms.</p>
                    </div>
                    <a href="guest-houses.php" class="bg-primary text-primary-foreground px-4 py-2 rounded-md font-medium hover:bg-primary/90 transition">
                        Book Now
                    </a>
                </div>

                <div>
                    <h2 class="text-2xl font-semibold mb-4">My Bookings</h2>
                    
                    <?php if (empty($bookings)): ?>
                    <div class="bg-muted p-8 text-center rounded-lg border border-dashed border-gray-300">
                        <p class="text-muted-foreground">You have no active bookings.</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($bookings as $booking): ?>
                        <div class="bg-white border rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($booking['gh_name']); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        Room <?php echo htmlspecialchars($booking['room_number']); ?> 
                                        <span class="text-gray-400">|</span> 
                                        Beds: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($booking['bed_numbers']); ?></span>
                                    </p>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        <?php echo date('M d', strtotime($booking['check_in'])); ?> - <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium 
                                        <?php 
                                            if ($booking['status'] === 'approved') echo 'bg-green-100 text-green-700';
                                            elseif ($booking['status'] === 'rejected') echo 'bg-red-100 text-red-700';
                                            elseif ($booking['status'] === 'checked_out') echo 'bg-gray-100 text-gray-700';
                                            else echo 'bg-yellow-100 text-yellow-700'; 
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                    </span>
                                    
                                    <?php if ($booking['status'] === 'approved'): ?>
                                    <button onclick="checkout(<?php echo $booking['id']; ?>)" class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                                        Check Out
                                    </button>
                                    <?php elseif ($booking['status'] === 'pending'): ?>
                                    <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" class="text-xs bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                        Cancel Request
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="assets/js/toast.js"></script>
    <script>
        async function cancelBooking(bookingId) {
            if (!confirm('Are you sure you want to cancel this booking request?')) return;

            try {
                const response = await fetch('api/cancel_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Booking Cancelled', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (e) {
                showToast('Network error', 'error');
            }
        }

        async function checkout(bookingId) {
            if (!confirm('Are you surely checking out? This will end your stay.')) return;
            
            try {
                const response = await fetch('api/checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId })
                });
                const res = await response.json();
                if (res.success) {
                    showToast('Checked out successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(res.message, 'error');
                }
            } catch (err) {
                showToast('Error processing checkout', 'error');
            }
        }
    </script>
</html>
