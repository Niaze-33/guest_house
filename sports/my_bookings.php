<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../middleware/role_check.php';

// Access control
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$current_page = 'sports_my_bookings';
$userId = $_SESSION['user_id'];

// Fetch User's Bookings
$stmt = $pdo->prepare("
    SELECT sb.*, sf.name as field_name, sf.location 
    FROM sports_bookings sb
    JOIN sports_fields sf ON sb.field_id = sf.id
    WHERE sb.user_id = ?
    ORDER BY sb.created_at DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sports Bookings - VarsityHub</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: hsl(222.2 47.4% 11.2%);
            --color-primary-foreground: hsl(210 40% 98%);
            --color-background: hsl(0 0% 100%);
            --color-foreground: hsl(222.2 47.4% 11.2%);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center gap-2 text-primary hover:text-primary/80 transition">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        <span class="font-bold text-lg">Back to Sports</span>
                    </a>
                </div>
                <div class="flex items-center gap-4">
                     <span class="text-sm font-medium text-gray-600">
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
                    </span>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">My Sports Bookings</h1>
        <p class="text-gray-600 mb-8">Track the status of your field reservation requests.</p>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <?php if (empty($bookings)): ?>
                <div class="p-12 text-center flex flex-col items-center justify-center">
                    <div class="bg-gray-50 p-4 rounded-full mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No bookings found</h3>
                    <p class="text-gray-500 mt-1">You haven't made any sports field reservation requests yet.</p>
                    <a href="index.php" class="mt-4 text-primary font-medium hover:underline">Book a Field</a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Field</th>
                                <th class="px-6 py-4">Date & Slot</th>
                                <th class="px-6 py-4">Purpose</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($bookings as $booking): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['field_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['location']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">
                                        <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 capitalize">
                                        <?php echo htmlspecialchars($booking['slot']); ?> Slot
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900"><?php echo htmlspecialchars($booking['purpose']); ?></div>
                                    <div class="text-xs text-gray-500">Team: <?php echo htmlspecialchars($booking['team_name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                        $statusConfig = [
                                            'pending' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'label' => 'Pending Review'],
                                            'approved' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'label' => 'Approved'],
                                            'rejected' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'label' => 'Rejected'],
                                            'cancelled' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => 'Cancelled'],
                                        ];
                                        $config = $statusConfig[$booking['status']] ?? $statusConfig['pending'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $config['bg'] . ' ' . $config['text']; ?>">
                                        <?php echo $config['label']; ?>
                                    </span>
                                    
                                    <?php if ($booking['status'] === 'rejected' && !empty($booking['rejection_reason'])): ?>
                                        <div class="text-xs text-red-600 mt-1 max-w-xs">
                                            Reason: <?php echo htmlspecialchars($booking['rejection_reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" 
                                                class="text-xs font-medium text-red-600 bg-white border border-red-200 hover:bg-red-50 px-3 py-1.5 rounded transition">
                                            Cancel Request
                                        </button>
                                    <?php elseif ($booking['status'] === 'approved'): ?>
                                        <span class="text-xs text-gray-400 italic">Contact Admin to Cancel</span>
                                    <?php else: ?>
                                        <span class="text-gray-300">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Toast Notification Script -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2"></div>
    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                warning: 'bg-yellow-500'
            };
            
            toast.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 transform transition-all duration-300 translate-y-10 opacity-0`;
            toast.innerHTML = `<span>${message}</span>`;
            
            container.appendChild(toast);
            
            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
            });
            
            // Remove after 3s
            setTimeout(() => {
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        async function cancelBooking(id) {
            if (!confirm('Are you sure you want to cancel this booking request? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('../api/sports_cancel.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: id })
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }
    </script>
</body>
</html>
