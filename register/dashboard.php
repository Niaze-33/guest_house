<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../middleware/role_check.php';

// Only 'register' role (and 'admin') can access this
requireRole(['register', 'admin']);

$view = $_GET['view'] ?? 'pending';
$current_page = $view;

// Fetch Stats for Sidebar
$pendingCount = $pdo->query("SELECT COUNT(*) FROM conference_bookings WHERE status = 'pending'")->fetchColumn();

// Fetch Data based on view
if ($view === 'rooms') {
    $stmt = $pdo->query("SELECT * FROM conference_rooms ORDER BY name ASC");
    $rooms = $stmt->fetchAll();
} else {
    $statusFilter = in_array($view, ['approved', 'rejected']) ? $view : 'pending';
    $stmt = $pdo->prepare("
        SELECT cb.*, cr.name as room_name, u.full_name, u.email, u.role as user_role, u.department
        FROM conference_bookings cb
        JOIN conference_rooms cr ON cb.conference_room_id = cr.id
        JOIN users u ON cb.user_id = u.id
        WHERE cb.status = ?
        ORDER BY cb.start_time DESC
        LIMIT 100
    ");
    $stmt->execute([$statusFilter]);
    $bookings = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conference Admin - VarsityHub</title>
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                Conf. Board
            </h1>
            <nav class="space-y-4">
                <div>
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Bookings</div>
                    <div class="space-y-1">
                        <a href="?view=pending" class="block px-4 py-2 rounded-md <?php echo $view === 'pending' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-gray-50 text-gray-700'; ?> flex justify-between items-center">
                            Pending
                            <?php if ($pendingCount > 0): ?>
                                <span class="bg-primary text-white text-[10px] px-1.5 py-0.5 rounded-full"><?php echo $pendingCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="?view=approved" class="block px-4 py-2 rounded-md <?php echo $view === 'approved' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-gray-50 text-gray-700'; ?>">Approved</a>
                        <a href="?view=rejected" class="block px-4 py-2 rounded-md <?php echo $view === 'rejected' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-gray-50 text-gray-700'; ?>">Rejected</a>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Facilities</div>
                    <div class="space-y-1">
                        <a href="?view=rooms" class="block px-4 py-2 rounded-md <?php echo $view === 'rooms' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-gray-50 text-gray-700'; ?>">Manage Rooms</a>
                    </div>
                </div>
            
                <a href="../logout.php" class="block px-4 py-2 rounded-md text-red-600 hover:bg-red-50 mt-8 font-medium">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto h-screen">
            <div class="flex justify-between items-center mb-6 text-primary">
                <div>
                    <h2 class="text-2xl font-bold capitalize">
                        <?php echo $view === 'rooms' ? 'Manage Conference Rooms' : ($view . ' Requests'); ?>
                    </h2>
                    <p class="text-gray-500 text-sm">
                        <?php echo $view === 'rooms' ? 'Configure venues and capacities' : 'Review and manage venue applications'; ?>
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <?php if ($view === 'rooms'): ?>
                        <button onclick="openAddRoomModal()" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90 transition flex items-center gap-2 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Add New Room
                        </button>
                    <?php endif; ?>
                    <div class="text-sm text-gray-500 border-l pl-4">
                        Logged in as <span class="font-medium text-gray-900 capitalize"><?php echo $_SESSION['role']; ?></span>
                    </div>
                </div>
            </div>

            <?php if ($view === 'rooms'): ?>
                <!-- Rooms Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Room Name</th>
                                <th class="px-6 py-4">Location</th>
                                <th class="px-6 py-4">Capacity</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 italic">
                            <?php if (empty($rooms)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">No rooms found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rooms as $room): ?>
                                <tr class="hover:bg-gray-50 transition drop-shadow-sm">
                                    <td class="px-6 py-4 font-bold text-gray-900"><?php echo htmlspecialchars($room['name']); ?></td>
                                    <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($room['location']); ?></td>
                                    <td class="px-6 py-4 text-gray-700"><?php echo $room['capacity']; ?> persons</td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="deleteRoom(<?php echo $room['id']; ?>, '<?php echo addslashes($room['name']); ?>')" class="text-red-600 hover:text-red-900 font-medium text-xs border border-red-200 px-3 py-1 rounded-md hover:bg-red-50 transition">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <!-- Bookings Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Applicant</th>
                                <th class="px-6 py-4">Venue</th>
                                <th class="px-6 py-4">Date & Time</th>
                                <th class="px-6 py-4">Role/Dept</th>
                                <?php if ($view === 'pending'): ?>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No <?php echo $view; ?> requests found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-700"><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="text-gray-900 font-medium"><?php echo date('M d, Y', strtotime($booking['start_time'])); ?></div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-gray-700 capitalize"><?php echo htmlspecialchars($booking['user_role']); ?></span>
                                            <span class="text-xs text-gray-400"><?php echo htmlspecialchars($booking['department']); ?></span>
                                        </div>
                                    </td>
                                    <?php if ($view === 'pending'): ?>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button onclick="updateStatus(<?php echo $booking['id']; ?>, 'approved')" class="bg-green-600 text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-green-700 transition">Approve</button>
                                                <button onclick="updateStatus(<?php echo $booking['id']; ?>, 'rejected')" class="bg-red-600 text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-red-700 transition">Reject</button>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Room Modal -->
    <div id="add-room-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 transition-opacity cursor-pointer" onclick="closeAddRoomModal()"></div>
        <div class="relative z-10 flex min-h-screen items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 transform transition-all border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Add New Conference Room</h3>
                <form onsubmit="submitAddRoom(event)" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Room Name</label>
                        <input type="text" id="room-name" required class="w-full border-gray-300 rounded-md shadow-sm border p-2.5 text-sm focus:ring-primary focus:border-primary" placeholder="e.g. Grand Hall">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input type="text" id="room-location" required class="w-full border-gray-300 rounded-md shadow-sm border p-2.5 text-sm focus:ring-primary focus:border-primary" placeholder="e.g. Level 3, Admin Building">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacity (Persons)</label>
                        <input type="number" id="room-capacity" required class="w-full border-gray-300 rounded-md shadow-sm border p-2.5 text-sm focus:ring-primary focus:border-primary" placeholder="e.g. 100">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeAddRoomModal()" class="text-gray-600 hover:text-gray-800 text-sm font-medium">Cancel</button>
                        <button type="submit" class="bg-primary text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-primary/90 transition shadow-sm">Save Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
        // Booking Status Logic
        async function updateStatus(id, status) {
            if(!confirm('Are you sure you want to ' + status + ' this request?')) return;

            try {
                const response = await fetch('../api/update_conference_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: id, status: status })
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

        // Room Management Logic
        function openAddRoomModal() {
            document.getElementById('add-room-modal').classList.remove('hidden');
        }

        function closeAddRoomModal() {
            document.getElementById('add-room-modal').classList.add('hidden');
        }

        async function submitAddRoom(e) {
            e.preventDefault();
            const name = document.getElementById('room-name').value;
            const location = document.getElementById('room-location').value;
            const capacity = document.getElementById('room-capacity').value;

            try {
                const response = await fetch('../api/add_conference_room.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, location, capacity })
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('An error occurred', 'error');
            }
        }

        async function deleteRoom(id, name) {
            if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) return;

            try {
                const response = await fetch('../api/delete_conference_room.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_id: id })
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('An error occurred', 'error');
            }
        }
    </script>
</body>
</html>
