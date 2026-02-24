<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../middleware/role_check.php';

// Access control: PE Admin ONLY (Main Admin cannot access)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pe_admin') {
    // If main admin tries to access, redirect them back to their own panel or home
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: ../admin/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

$current_page = 'sports_admin';
$status = $_GET['status'] ?? 'pending';

// Valid statuses
$validStatuses = ['pending', 'approved', 'rejected', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    $status = 'pending';
}

// Fetch Bookings by Status
$stmt = $pdo->prepare("
    SELECT sb.*, sf.name as field_name, u.full_name as requester_name 
    FROM sports_bookings sb
    JOIN sports_fields sf ON sb.field_id = sf.id
    JOIN users u ON sb.user_id = u.id
    WHERE sb.status = ?
    ORDER BY sb.created_at DESC
");
$stmt->execute([$status]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PE Board Dashboard - VarsityHub</title>
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
<body class="bg-gray-100 text-gray-900 font-sans">
    
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Sidebar -->
        <aside class="w-full md:w-64 bg-white border-r border-gray-200 min-h-screen p-6 hidden md:block">
            <h1 class="text-xl font-bold mb-8 flex items-center gap-2">
                 <div class="bg-primary/10 p-2 rounded-lg text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                PE Board
            </h1>
            <nav class="space-y-4">
                <div>
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Bookings</div>
                    <div class="space-y-1">
                        <a href="?status=pending" class="block px-4 py-2 rounded-md <?php echo $status === 'pending' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-gray-50 text-gray-700'; ?>">Pending Requests</a>
                        <a href="?status=approved" class="block px-4 py-2 rounded-md <?php echo $status === 'approved' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-gray-50 text-gray-700'; ?>">Approved</a>
                        <a href="?status=rejected" class="block px-4 py-2 rounded-md <?php echo $status === 'rejected' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-gray-50 text-gray-700'; ?>">Rejected</a>
                        <a href="?status=cancelled" class="block px-4 py-2 rounded-md <?php echo $status === 'cancelled' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-gray-50 text-gray-700'; ?>">Cancelled</a>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Facilities</div>
                    <div class="space-y-1">
                        <a href="manage_fields.php" class="block px-4 py-2 rounded-md hover:bg-gray-50 text-gray-700">Manage Fields</a>
                    </div>
                </div>
            
                <a href="../logout.php" class="block px-4 py-2 rounded-md text-red-600 hover:bg-red-50 mt-8">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto h-screen">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold capitalize"><?php echo $status; ?> Requests</h2>
                <div class="text-sm text-gray-500">
                    Logged in as Admin
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3">Requester</th>
                                <th class="px-6 py-3">Field</th>
                                <th class="px-6 py-3">Date & Slot</th>
                                <th class="px-6 py-3">Purpose & Team</th>
                                <th class="px-6 py-3">Participants</th>
                                <th class="px-6 py-3">Submitted</th>
                                <?php if ($status === 'pending'): ?>
                                <th class="px-6 py-3 text-right">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">No <?php echo $status; ?> requests found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($booking['requester_name']); ?></td>
                                    <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($booking['field_name']); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="text-gray-900"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                                        <div class="text-xs text-gray-500 capitalize"><?php echo htmlspecialchars($booking['slot']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-gray-900"><?php echo htmlspecialchars($booking['purpose']); ?></div>
                                        <div class="text-xs text-gray-500">Team: <?php echo htmlspecialchars($booking['team_name']); ?></div>
                                        <?php if (!empty($booking['notes'])): ?>
                                            <div class="text-xs text-gray-400 italic mt-1 max-w-[200px] truncate" title="<?php echo htmlspecialchars($booking['notes']); ?>"><?php echo htmlspecialchars($booking['notes']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 text-center"><?php echo $booking['participants']; ?></td>
                                    <td class="px-6 py-4 text-gray-500 text-xs text-center">
                                        <?php echo date('M d H:i', strtotime($booking['created_at'])); ?>
                                    </td>
                                    <?php if ($status === 'pending'): ?>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button onclick="updateStatus(<?php echo $booking['id']; ?>, 'approve')" class="bg-green-600 text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-green-700">Approve</button>
                                            <button onclick="openRejectModal(<?php echo $booking['id']; ?>)" class="bg-red-600 text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-red-700">Reject</button>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Reject Modal -->
    <div id="reject-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 transition-opacity cursor-pointer" onclick="closeRejectModal()"></div>
        <div class="relative z-10 flex min-h-screen items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 transform transition-all">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Booking Request</h3>
                <form onsubmit="submitReject(event)">
                    <input type="hidden" id="reject-booking-id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection</label>
                        <select id="reject-reason" class="w-full border-gray-300 rounded-md shadow-sm border p-2 text-sm">
                            <option value="Scheduling Conflict">Scheduling Conflict</option>
                            <option value="Maintenance">Field Maintenance</option>
                            <option value="Incomplete Details">Incomplete Details</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Additional Comments</label>
                        <textarea id="reject-comment" rows="3" class="w-full border-gray-300 rounded-md shadow-sm border p-2 text-sm" placeholder="Optional explanation..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeRejectModal()" class="text-gray-600 hover:text-gray-800 text-sm font-medium">Cancel</button>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">Confirm Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
        async function updateStatus(id, action, reason = '') {
            if (!confirm(`Are you sure you want to ${action} this request?`)) return;

            try {
                const response = await fetch('../api/sports_admin_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        booking_id: id, 
                        action: action,
                        reason: reason
                    })
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('An error occurred', 'error');
            }
        }

        function openRejectModal(id) {
            document.getElementById('reject-booking-id').value = id;
            document.getElementById('reject-modal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('reject-modal').classList.add('hidden');
        }

        function submitReject(e) {
            e.preventDefault();
            const id = document.getElementById('reject-booking-id').value;
            const reasonType = document.getElementById('reject-reason').value;
            const comment = document.getElementById('reject-comment').value;
            
            const fullReason = comment ? `${reasonType}: ${comment}` : reasonType;
            
            updateStatus(id, 'reject', fullReason);
            closeRejectModal();
        }
    </script>
</body>
</html>
