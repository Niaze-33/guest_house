<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../middleware/role_check.php';

requireConferenceAccess();

$userId = $_SESSION['user_id'];

// Fetch User's Bookings
$stmt = $pdo->prepare("
    SELECT cb.*, cr.name as room_name 
    FROM conference_bookings cb
    JOIN conference_rooms cr ON cb.conference_room_id = cr.id
    WHERE cb.user_id = ?
    ORDER BY cb.created_at DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Conference Bookings - VarsityHub</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: hsl(222.2 47.4% 11.2%);
            --color-primary-foreground: hsl(210 40% 98%);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">
    
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="index.php" class="font-bold text-xl text-primary flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Booking
                </a>
                <div class="flex items-center gap-4">
                     <span class="text-sm font-medium text-gray-600">
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
                    </span>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 border px-3 py-1 rounded">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-10">
        <h1 class="text-2xl font-bold mb-6">My Booking History</h1>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <?php if (empty($bookings)): ?>
                <div class="p-8 text-center text-gray-500">You haven't made any conference room bookings yet.</div>
            <?php else: ?>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">Room</th>
                            <th class="px-6 py-4">Date & Time</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Submitted</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($bookings as $booking): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($booking['room_name']); ?></td>
                            <td class="px-6 py-4">
                                <div><?php echo date('M d, Y', strtotime($booking['start_time'])); ?></div>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800'
                                    ];
                                    $color = $statusColors[$booking['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $color; ?> capitalize">
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if (in_array($booking['status'], ['pending', 'approved'])): ?>
                                    <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" class="text-xs text-red-600 hover:text-red-900 font-medium border border-red-200 px-2 py-1 rounded hover:bg-red-50">Cancel</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/toast.js"></script>
    <script>
        async function cancelBooking(id) {
            if(!confirm('Are you sure you want to CANCEL this booking?')) return;

            try {
                const response = await fetch('../api/cancel_conference_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: id })
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred', 'error');
            }
        }
    </script>
</body>
</html>
