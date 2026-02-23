<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

// Handle Add Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_room') {
    $guestHouseId = intval($_POST['guest_house_id']);
    $roomNumber = sanitize($_POST['room_number']);
    $type = sanitize($_POST['type']);
    
    // Determine beds based on type
    $bedCount = 0;
    if ($type === 'Single') $bedCount = 1;
    elseif ($type === 'Double') $bedCount = 2;
    elseif ($type === 'Triple') $bedCount = 3;

    try {
        $pdo->beginTransaction();
        
        // Insert Room
        $stmt = $pdo->prepare("INSERT INTO rooms (guest_house_id, room_number, type, status) VALUES (?, ?, ?, 'available')");
        $stmt->execute([$guestHouseId, $roomNumber, $type]);
        $roomId = $pdo->lastInsertId();

        // Insert Beds
        $stmtBed = $pdo->prepare("INSERT INTO beds (room_id, bed_number) VALUES (?, ?)");
        for ($i = 0; $i < $bedCount; $i++) {
            $suffix = chr(65 + $i); // A, B, C...
            $bedNumber = "$roomNumber-$suffix";
            $stmtBed->execute([$roomId, $bedNumber]);
        }
        
        $pdo->commit();
        $successMsg = "Room $roomNumber added successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMsg = "Error adding room: " . $e->getMessage();
    }
}

// Handle Delete Room
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$deleteId]);
        $successMsg = "Room deleted.";
    } catch (Exception $e) {
        $errorMsg = "Error deleting room: " . $e->getMessage();
    }
}

// Handle VIP Reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve_vip') {
    $roomId = intval($_POST['room_id']);
    $startDate = sanitize($_POST['start_date']);
    $endDate = sanitize($_POST['end_date']);
    
    if ($startDate && $endDate && $roomId) {
        try {
            $pdo->beginTransaction();
            
            // 1. Check if ANY bed in this room is booked for this range
            $stmtCheck = $pdo->prepare("
                SELECT COUNT(*) FROM bookings b
                JOIN booking_beds bb ON b.id = bb.booking_id
                WHERE b.room_id = ?
                AND b.status IN ('pending', 'approved')
                AND (b.check_in < ? AND b.check_out > ?)
            ");
            $stmtCheck->execute([$roomId, $endDate, $startDate]);
            
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Room is not empty for these dates.");
            }
            
            // 2. Get all beds in room
            $stmtBeds = $pdo->prepare("SELECT id FROM beds WHERE room_id = ?");
            $stmtBeds->execute([$roomId]);
            $beds = $stmtBeds->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($beds)) {
                throw new Exception("Room has no beds to book.");
            }
            
            // 3. Create Bookings for ALL beds
            // Using Admin ID (Current User) as the 'VIP' holder
            $adminId = $_SESSION['user_id'];
            
            foreach ($beds as $bedId) {
                $stmtBook = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, status) VALUES (?, ?, ?, ?, 'approved')");
                $stmtBook->execute([$adminId, $roomId, $startDate, $endDate]);
                $bookingId = $pdo->lastInsertId();
                
                $stmtLink = $pdo->prepare("INSERT INTO booking_beds (booking_id, bed_id) VALUES (?, ?)");
                $stmtLink->execute([$bookingId, $bedId]);
            }
            
            $pdo->commit();
            $successMsg = "Room reserved for VIP successfully.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = "VIP Reservation Failed: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Invalid dates provided.";
    }
}

// Handle Clear VIP Reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_vip') {
    $roomId = intval($_POST['room_id']);
    
    if ($roomId) {
        try {
            // Delete bookings for this room made by any ADMIN user
            // We need to identify admin users. Or just delete bookings made by CURRENT admin header?
            // "Admin can book... no cancel option". 
            // Safest: Delete bookings for this room where user role is 'admin'.
            
            $stmt = $pdo->prepare("
                DELETE b FROM bookings b
                JOIN users u ON b.user_id = u.id
                WHERE b.room_id = ? 
                AND u.role = 'admin'
                AND b.status = 'approved'
                AND b.check_in >= CURDATE()
            ");
            $stmt->execute([$roomId]);
            
            // Check row count?
            if ($stmt->rowCount() > 0) {
                 $successMsg = "VIP Reservations cleared for this room (Future dates).";
            } else {
                 $errorMsg = "No active VIP reservations found to clear.";
            }

        } catch (Exception $e) {
            $errorMsg = "Error clearing VIP: " . $e->getMessage();
        }
    }
}

// Fetch all rooms
$stmt = $pdo->query("
    SELECT r.*, gh.name as gh_name, 
    (SELECT COUNT(*) FROM beds WHERE room_id = r.id) as total_beds,
    (SELECT COUNT(*) FROM beds WHERE room_id = r.id AND is_booked = 1) as booked_beds,
    (SELECT COUNT(*) 
     FROM bookings b 
     JOIN users u ON b.user_id = u.id 
     WHERE b.room_id = r.id 
     AND u.role = 'admin' 
     AND b.status = 'approved' 
     AND b.check_in >= CURDATE()) as is_vip_booked
    FROM rooms r
    JOIN guest_houses gh ON r.guest_house_id = gh.id
    ORDER BY gh.name, r.room_number
");
$rooms = $stmt->fetchAll();

// Fetch Guest Houses
$ghStmt = $pdo->query("SELECT * FROM guest_houses");
$guestHouses = $ghStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Rooms - University Guest House</title>
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
<body class="bg-gray-100 font-sans text-gray-900">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Sidebar -->
        <aside class="w-full md:w-64 bg-white border-r border-gray-200 min-h-screen p-6">
            <h1 class="text-xl font-bold mb-8">Admin Panel</h1>
            <nav class="space-y-2">
                <a href="index.php" class="block px-4 py-2 rounded-md hover:bg-gray-50 text-gray-700">Overview</a>
                <a href="bookings.php" class="block px-4 py-2 rounded-md hover:bg-gray-50 text-gray-700">Bookings</a>
                <a href="rooms.php" class="block px-4 py-2 rounded-md bg-blue-50 text-blue-700 font-medium">Rooms</a>
                <a href="../logout.php" class="block px-4 py-2 rounded-md text-red-600 hover:bg-red-50 mt-8">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-6">Manage Rooms</h2>

            <?php if (isset($successMsg)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"><?php echo $successMsg; ?></div>
            <?php endif; ?>
            <?php if (isset($errorMsg)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"><?php echo $errorMsg; ?></div>
            <?php endif; ?>

            <!-- Add Room Form -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-8">
                <h3 class="text-lg font-semibold mb-4">Add New Room</h3>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <input type="hidden" name="action" value="add_room">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guest House</label>
                        <select name="guest_house_id" class="w-full rounded-md border-gray-300 shadow-sm border p-2" required>
                            <?php foreach ($guestHouses as $gh): ?>
                                <option value="<?php echo $gh['id']; ?>"><?php echo htmlspecialchars($gh['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Room Number</label>
                        <input type="text" name="room_number" placeholder="e.g. 101" class="w-full rounded-md border-gray-300 shadow-sm border p-2" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select name="type" class="w-full rounded-md border-gray-300 shadow-sm border p-2" required>
                            <option value="Single">Single (1 Bed)</option>
                            <option value="Double">Double (2 Beds)</option>
                            <option value="Triple">Triple (3 Beds)</option>
                        </select>
                    </div>

                    <button type="submit" class="bg-blue-900 text-white px-4 py-2 rounded-md font-medium hover:bg-blue-800 h-10">Add Room</button>
                </form>
            </div>

            <!-- Rooms List -->
            <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-medium">
                        <tr>
                            <th class="px-6 py-3">Guest House</th>
                            <th class="px-6 py-3">Room Num</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Beds (Booked/Total)</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($rooms as $room): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($room['gh_name']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($room['room_number']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($room['type']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $room['status'] === 'available' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                    <?php echo ucfirst($room['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4"><?php echo $room['booked_beds'] . '/' . $room['total_beds']; ?></td>
                            <td class="px-6 py-4 text-right flex gap-2 justify-end">
                                <button onclick="openVipModal(<?php echo $room['id']; ?>, '<?php echo $room['room_number']; ?>')" class="text-indigo-600 hover:text-indigo-900 border border-indigo-200 px-2 py-1 rounded text-xs">Reserve VIP</button>
                                <?php if ($room['is_vip_booked'] > 0): ?>
                                <form method="POST" onsubmit="return confirm('Clear ALL future VIP reservations for this room?');" class="inline">
                                    <input type="hidden" name="action" value="cancel_vip">
                                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                    <button type="submit" class="text-orange-600 hover:text-orange-900 border border-orange-200 px-2 py-1 rounded text-xs">Clear VIP</button>
                                </form>
                                <?php endif; ?>
                                <a href="?delete_id=<?php echo $room['id']; ?>" onclick="return confirm('Are you sure? This will delete all beds and bookings for this room.')" class="text-red-600 hover:text-red-900 px-2 py-1">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- VIP Modal -->
    <div id="vip-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900">VIP Room Reservation</h3>
                <form method="POST" class="mt-4">
                    <input type="hidden" name="action" value="reserve_vip">
                    <input type="hidden" id="vip_room_id" name="room_id">
                    
                    <p class="text-sm text-gray-500 mb-4">Reserving Room <span id="vip_room_num" class="font-bold"></span> for VIP.</p>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Check In</label>
                        <input type="date" name="start_date" id="start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Check Out</label>
                        <input type="date" name="end_date" id="end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2" required>
                    </div>

                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" onclick="document.getElementById('vip-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openVipModal(id, number) {
            document.getElementById('vip_room_id').value = id;
            document.getElementById('vip_room_num').innerText = number;
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').min = today; 
            document.getElementById('end_date').min = today;
            
            document.getElementById('vip-modal').classList.remove('hidden');
        }
    </script>

</body>
</html>
