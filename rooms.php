<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$guestHouseId = isset($_GET['house_id']) ? intval($_GET['house_id']) : 0;
$currentUserGender = $_SESSION['gender'] ?? '';

if (!$guestHouseId) {
    redirect('guest-houses.php');
}

// 1. Determine Date Range (Default: Today + 10 Days)
$defaultDays = 10;
$checkIn = isset($_GET['check_in']) ? sanitize($_GET['check_in']) : date('Y-m-d');
$checkOut = isset($_GET['check_out']) ? sanitize($_GET['check_out']) : date('Y-m-d', strtotime("+$defaultDays days"));

// Ensure valid range
if ($checkIn > $checkOut) {
    $checkOut = date('Y-m-d', strtotime($checkIn . " +$defaultDays days"));
}

// Generate Date Array
$begin = new DateTime($checkIn);
$end = new DateTime($checkOut);
$end->modify('+1 day'); // Include end date in loop
$interval = DateInterval::createFromDateString('1 day');
$datePeriod = new DatePeriod($begin, $interval, $end);

$dates = [];
foreach ($datePeriod as $dt) {
    $dates[] = $dt->format('Y-m-d');
}

// 2. Fetch Guest House Info
$stmt = $pdo->prepare("SELECT * FROM guest_houses WHERE id = ?");
$stmt->execute([$guestHouseId]);
$guestHouse = $stmt->fetch();

if (!$guestHouse) {
    die("Guest House not found");
}

// 3. Fetch Rooms & Beds
$stmtRooms = $pdo->prepare("SELECT * FROM rooms WHERE guest_house_id = ? AND status = 'available'");
$stmtRooms->execute([$guestHouseId]);
$rooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

// Map Beds by Room
$bedsByRoom = [];
$allBedroomIds = []; // For fetching availability
foreach ($rooms as $room) {
    $stmtBeds = $pdo->prepare("SELECT * FROM beds WHERE room_id = ?");
    $stmtBeds->execute([$room['id']]);
    $beds = $stmtBeds->fetchAll(PDO::FETCH_ASSOC);
    $bedsByRoom[$room['id']] = $beds;
    foreach ($beds as $bed) {
        $allBedroomIds[] = $bed['id'];
    }
}

// 4. Fetch Availability / Bookings within Range
$bookedMap = []; // [bed_id][date] => info
$roomGenderMap = []; // [room_id] => 'Male'/'Female' (if occupied)

if (!empty($allBedroomIds)) {
    // A. Fetch Bookings overlapping range
    // We need to fetch bookings that check_in < view_end AND check_out > view_start
    $placeholders = implode(',', array_fill(0, count($allBedroomIds), '?'));
    $sql = "
        SELECT 
            b.id as booking_id, b.room_id, b.check_in, b.check_out, b.status, b.user_id,
            bb.bed_id,
            u.full_name, u.gender, u.designation, u.department, u.role as user_role
        FROM booking_beds bb
        JOIN bookings b ON bb.booking_id = b.id
        JOIN users u ON b.user_id = u.id
        WHERE bb.bed_id IN ($placeholders)
        AND b.status IN ('pending', 'approved')
        AND (b.check_in < ? AND b.check_out > ?)
    ";
    
    // Params: bed_ids..., checkOut, checkIn
    $params = array_merge($allBedroomIds, [$checkOut, $checkIn]);
    
    $stmtBookings = $pdo->prepare($sql);
    $stmtBookings->execute($params);
    $bookings = $stmtBookings->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bookings as $bk) {
        $bStart = new DateTime($bk['check_in']);
        $bEnd = new DateTime($bk['check_out']);
        
        $period = new DatePeriod($bStart, $interval, $bEnd);
        foreach ($period as $dt) {
            $dStr = $dt->format('Y-m-d');
            if ($dStr >= $checkIn && $dStr <= $checkOut) {
                // Mark Bed as Booked
                $bookedMap[$bk['bed_id']][$dStr] = [
                    'full_name' => $bk['full_name'],
                    'department' => $bk['department'],
                    'designation' => $bk['designation'],
                    'gender' => $bk['gender'],
                    'booking_id' => $bk['booking_id'],
                    'status' => $bk['status'],
                    'user_role' => $bk['user_role'],
                    'is_own' => ($bk['user_id'] == $_SESSION['user_id'])
                ];

                // Mark Room-Day as Gender Locked
                if (!isset($roomGenderMap[$bk['room_id']][$dStr])) {
                    $roomGenderMap[$bk['room_id']][$dStr] = $bk['gender'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Book Rooms - <?php echo htmlspecialchars($guestHouse['name']); ?></title>
    <!-- ... Head Scripts ... -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: 'hsl(222.2 47.4% 11.2%)',
                            foreground: 'hsl(210 40% 98%)',
                        },
                        background: 'hsl(0 0% 100%)',
                        foreground: 'hsl(222.2 47.4% 11.2%)',
                        muted: 'hsl(210 40% 96.1%)',
                    }
                }
            }
        }
    </script>
    <style>
        /* ... styles ... */
        .grid-scroll::-webkit-scrollbar { height: 8px; }
        .grid-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .grid-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .bed-chip { transition: all 0.2s; }
        .bed-chip.selected { background-color: #facc15; color: #713f12; border-color: #facc15; transform: scale(1.1); font-weight: bold; }
        .hidden-row { display: none; }
    </style>
</head>
<body class="bg-background text-foreground font-sans flex flex-col h-screen overflow-hidden">
    
    <!-- ... Header (Skipping for brevity, no changes needed) ... -->
    <header class="bg-primary text-primary-foreground py-4 px-6 shadow-md z-50 shrink-0">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <a href="guest-houses.php" class="hover:bg-white/10 p-2 rounded-full transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="text-xl font-bold"><?php echo htmlspecialchars($guestHouse['name']); ?></h1>
            </div>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer bg-white/10 px-3 py-1.5 rounded hover:bg-white/20 transition select-none">
                    <input type="checkbox" id="show-available-only" class="rounded text-blue-500 focus:ring-0 cursor-pointer" onchange="toggleAvailableOnly()">
                    <span class="text-sm font-medium text-white">Hide Fully Booked</span>
                </label>
                <form method="GET" class="flex items-center gap-2 text-black">
                    <input type="hidden" name="house_id" value="<?php echo $guestHouseId; ?>">
                    <?php 
                        $minDate = date('Y-m-d');
                        $maxDate = date('Y-m-d', strtotime('+10 days'));
                    ?>
                    <input type="date" name="check_in" min="<?php echo $minDate; ?>" max="<?php echo $maxDate; ?>" value="<?php echo $checkIn; ?>" class="rounded px-2 py-1 text-sm border-0 focus:ring-2 focus:ring-blue-500 bg-white" onchange="this.form.submit()">
                    <span class="text-primary-foreground text-sm font-medium">to</span>
                    <input type="date" name="check_out" min="<?php echo $minDate; ?>" max="<?php echo $maxDate; ?>" value="<?php echo $checkOut; ?>" class="rounded px-2 py-1 text-sm border-0 focus:ring-2 focus:ring-blue-500 bg-white" onchange="this.form.submit()">
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-hidden flex flex-col relative w-full">
        
        <!-- Legend -->
        <div class="bg-white border-b border-gray-200 px-6 py-2 flex flex-wrap gap-4 text-xs shrink-0 items-center justify-center">
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded border border-green-300 bg-white"></div>
                <span class="text-gray-600">Available</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded border border-gray-400 bg-gray-300"></div>
                <span class="text-gray-700 font-medium">Booked (Same Gender)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded border border-red-500 bg-red-500"></div>
                <span class="text-red-700 font-bold">VIP Reserved</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded border border-gray-200 bg-gray-100"></div>
                <span class="text-gray-400">Occupied (Other)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded border border-red-100 bg-red-50"></div>
                <span class="text-red-400">Restricted</span>
            </div>
             <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded border border-yellow-400 bg-yellow-400"></div>
                <span class="text-yellow-700 font-bold">Selected</span>
            </div>
        </div>

        <!-- Grid Container -->
        <div class="flex-1 overflow-auto grid-scroll bg-gray-50/50">
            <div class="min-w-max p-6">
                <div class="bg-white rounded-lg shadow border border-gray-200">
                <!-- Table Header -->
                <div class="flex border-b border-gray-300 sticky top-0 z-40 shadow-sm">
                    <div class="w-48 p-4 font-bold text-gray-700 bg-gray-100 border-r border-gray-300 shrink-0 sticky left-0 z-50 shadow-[4px_0_24px_-10px_rgba(0,0,0,0.1)]">Room</div>
                    <?php foreach ($dates as $date): ?>
                    <div class="w-32 p-3 text-center border-r border-gray-300 last:border-0 bg-gray-50">
                        <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider"><?php echo date('D', strtotime($date)); ?></div>
                        <div class="text-lg font-bold text-gray-800"><?php echo date('d M', strtotime($date)); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Table Body -->
                <?php foreach ($rooms as $room): ?>
                <?php 
                    $roomId = $room['id'];
                    $beds = $bedsByRoom[$roomId] ?? [];
                    // Room locked logic removed from here, moved to per-cell check
                    
                    // Check availability for filtering
                    $totalFreeBedsInRow = 0;
                ?>
                <div class="flex border-b border-gray-300 last:border-0 hover:bg-gray-50/50 transition room-row" data-room-id="<?php echo $roomId; ?>">
                    
                    <!-- Room Info (Sticky Left) -->
                    <div class="w-48 p-4 border-r border-gray-300 shrink-0 sticky left-0 z-30 bg-gray-50 shadow-[4px_0_24px_-10px_rgba(0,0,0,0.05)]">
                         <div class="font-bold text-gray-900">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                         <div class="text-xs text-gray-500"><?php echo htmlspecialchars($room['type']); ?></div>
                         <!-- Availability Counter -->
                         <input type="hidden" class="row-calculated-avail" value="__AVAILABILITY_PLACEHOLDER__">
                    </div>

                    <?php foreach ($dates as $date): ?>
                    <div class="w-32 p-2 border-r border-gray-300 last:border-0 flex flex-wrap content-start gap-1.5 relative group">
                        <?php 
                            // Determine if this day is gender locked for this room
                            $activeGender = $roomGenderMap[$roomId][$date] ?? null;
                            $isRestricted = ($activeGender && $activeGender !== $currentUserGender);
                        ?>
                        <?php foreach ($beds as $bed): ?>
                        <?php 
                            $bedId = $bed['id'];
                            $bookingInfo = $bookedMap[$bedId][$date] ?? null;
                            $isBooked = !empty($bookingInfo);
                            
                            $statusClass = 'bg-white border-green-300 text-green-700 hover:bg-green-50 cursor-pointer hover:border-green-400 hover:shadow-sm'; 
                            $statusAttr = "onclick=\"toggleSelection($bedId, '$date', this)\"";
                            $title = "Available";

                            if ($isBooked) {
                                // Check if Admin/VIP booking
                                if ($bookingInfo['user_role'] === 'admin') {
                                     $statusClass = 'bg-red-500 border-red-600 text-white cursor-not-allowed';
                                     $title = "Reserved (VIP)";
                                     $statusAttr = "onclick='alert(\"This bed is reserved for VIP guests.\")'";
                                } else {
                                    $canView = ($bookingInfo['gender'] === $currentUserGender);
                                    // Darkened from bg-gray-100 to bg-gray-300/400 for better visibility
                                    $statusClass = $canView ? 'bg-gray-300 border-gray-400 text-gray-700 cursor-pointer hover:bg-gray-400 font-medium' : 'bg-gray-100 border-gray-200 text-gray-300 cursor-not-allowed';
                                    $title = $canView ? "Booked by " . htmlspecialchars($bookingInfo['full_name']) : "Occupied";
                                    
                                    if ($canView) {
                                        $jsonInfo = htmlspecialchars(json_encode($bookingInfo), ENT_QUOTES, 'UTF-8');
                                        $statusAttr = "onclick='showBookerInfo($jsonInfo)'";
                                    } else {
                                        $statusAttr = '';
                                    }
                                }
                            } elseif ($isRestricted) {
                                // If day is restricted, disable empty beds
                                $statusClass = 'bg-red-50 border-red-100 text-red-300 cursor-not-allowed opacity-60';
                                $statusAttr = '';
                                $title = "Gender Restricted ($activeGender Only)";
                            } else {
                                $totalFreeBedsInRow++;
                            }
                        ?>
                        
                        <div class="bed-chip relative w-8 h-8 rounded-md border text-xs flex items-center justify-center select-none <?php echo $statusClass; ?> cursor-pointer" 
                             <?php echo $statusAttr; ?> 
                             title="<?php echo $title; ?>">
                            <?php echo $bed['bed_number']; ?>
                        </div>

                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Inject Availability Value via JS buffer or output buffering if possible, or just simpler PHP logic:
                     We can't retroactively update the hidden input in the sticky col easily without buffer.
                     Alternative: Put the input at the end (as before) but wrap it in a 0x0 div?
                     Standard Flex behavior ignores hidden inputs usually.
                     Let's stick to putting it at the end but wrap in a Hidden Div.
                -->
                <div class="hidden">
                    <input type="hidden" class="row-calculated-avail" value="<?php echo $totalFreeBedsInRow; ?>">
                    <script>
                        // Small inline script to update the placeholder (hacky but works for PHP output order)
                        // Actually, just reading this value in JS is easier.
                        // I'll update the JS function to look for `.row-calculated-avail` instead.
                    </script>
                </div>

                <?php endforeach; ?>

                </div>
            </div>
        </div>

        <!-- Sticky Footer Selection Panel -->
        <div id="booking-panel" class="bg-white border-t border-gray-200 p-4 shadow-[0_-5px_15px_-5px_rgba(0,0,0,0.1)] transform translate-y-full transition-transform duration-300 z-50 absolute bottom-0 w-full">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                        <span id="selected-count" class="bg-primary text-white text-xs px-2 py-0.5 rounded-full">0</span> 
                        Items Selected
                    </h3>
                    <p class="text-xs text-gray-500 mt-1" id="selection-summary">Select dates to book</p>
                </div>
                <div class="flex gap-3 w-full sm:w-auto">
                    <button onclick="clearSelection()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition w-full sm:w-auto">
                        Clear
                    </button>
                    <button onclick="confirmBooking()" class="px-6 py-2 text-sm font-bold text-white bg-primary hover:bg-primary/90 rounded-lg shadow-sm transition w-full sm:w-auto">
                        Book Selected
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Info Modal -->
    <div id="info-modal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-[100] p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden transform transition-all scale-95 opacity-0" id="modal-content">
            <div class="bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
                <h3 class="font-bold text-gray-900">Bed Status</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Occupant</label>
                    <div class="text-lg font-medium text-gray-900" id="modal-name">Name</div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Dept</label>
                        <div class="text-sm text-gray-700" id="modal-dept">Dept</div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Designation</label>
                        <div class="text-sm text-gray-700" id="modal-designation">Role</div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                     <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800" id="modal-status">
                        Reserved
                     </span>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/toast.js"></script>
    <script>
        // State: { 'bedID_YYYY-MM-DD': { bedId, date, element } }
        const selectedCells = new Map();

        function toggleSelection(bedId, date, el) {
            const key = `${bedId}_${date}`;
            
            if (selectedCells.has(key)) {
                // Deselect
                selectedCells.delete(key);
                el.classList.remove('selected');
            } else {
                // Select
                selectedCells.set(key, { bedId, date, el });
                el.classList.add('selected');
            }
            
            updatePanel();
        }

        function clearSelection() {
            selectedCells.forEach(item => {
                item.el.classList.remove('selected');
            });
            selectedCells.clear();
            updatePanel();
        }

        function updatePanel() {
            const count = selectedCells.size;
            document.getElementById('selected-count').innerText = count;
            
            const panel = document.getElementById('booking-panel');
            if (count > 0) {
                panel.classList.remove('translate-y-full');
            } else {
                panel.classList.add('translate-y-full');
            }

            // Summarize (Optional simple summary)
            document.getElementById('selection-summary').innerText = 
                count === 0 ? 'Select dates to book' : 'Review selections before confirming';
        }

        function showBookerInfo(info) {
            console.log("Booker Info:", info); // Debugging
            const modal = document.getElementById('info-modal');
            const content = document.getElementById('modal-content');
            
            document.getElementById('modal-name').textContent = info.full_name;
            document.getElementById('modal-dept').textContent = info.department;
            document.getElementById('modal-designation').textContent = info.designation;
            
            // Status Badge
            const statusEl = document.getElementById('modal-status');
            statusEl.textContent = info.status.charAt(0).toUpperCase() + info.status.slice(1);
            statusEl.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                info.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'
            }`;

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('info-modal');
            const content = document.getElementById('modal-content');
            
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        function toggleAvailableOnly() {
            const checkbox = document.getElementById('show-available-only');
            const rows = document.querySelectorAll('.room-row');

            rows.forEach(row => {
                const availability = parseInt(row.querySelector('.row-calculated-avail') ? row.querySelector('.row-calculated-avail').value : 0) || 0;
                
                if (checkbox.checked) {
                    // Hide if availability is 0
                    if (availability === 0) {
                        row.classList.add('hidden-row');
                    } else {
                        row.classList.remove('hidden-row');
                    }
                } else {
                    // Show all
                    row.classList.remove('hidden-row');
                }
            });
        }
        
        async function confirmBooking() {
            if (selectedCells.size === 0) return;

            // 1. Group Selections into Ranges per Bed
            const bedDates = {}; // { bedId: [date1, date2, ...] }

            selectedCells.forEach(item => {
                if (!bedDates[item.bedId]) bedDates[item.bedId] = [];
                bedDates[item.bedId].push(item.date);
            });

            const bookings = [];

            // 2. Process each bed's dates to find ranges
            for (const [bedId, dates] of Object.entries(bedDates)) {
                // Sort dates
                dates.sort();

                let startDate = dates[0];
                let prevDate = new Date(dates[0]);

                for (let i = 1; i <= dates.length; i++) {
                    const currentDateStr = dates[i];
                    const currentDate = currentDateStr ? new Date(currentDateStr) : null;
                    
                    // Check continuity: Current = Prev + 1 day
                    const expectedDate = new Date(prevDate);
                    expectedDate.setDate(expectedDate.getDate() + 1);
                    
                    const isContinuous = currentDate && currentDate.getTime() === expectedDate.getTime();

                    if (!isContinuous) {
                        // End of a range
                        // API expects check_out to be the day AFTER the last night (Hotel logic)
                        // If I book Feb 04, CheckIn=04, CheckOut=05
                        // If I book Feb 04, 05. CheckIn=04, CheckOut=06
                        
                        // Calculate CheckOut Date (Last Booked Date + 1 Day)
                        const checkOutDate = new Date(prevDate);
                        checkOutDate.setDate(checkOutDate.getDate() + 1);
                        
                        bookings.push({
                            bed_id: bedId,
                            check_in: startDate,
                            check_out: checkOutDate.toISOString().split('T')[0]
                        });

                        // Start new range
                        if (currentDateStr) {
                            startDate = currentDateStr;
                            prevDate = currentDate;
                        }
                    } else {
                        prevDate = currentDate;
                    }
                }
            }

            // 3. Confirm
            const confirmMsg = `You are about to make ${bookings.length} booking(s). Proceed?`;
            if (!confirm(confirmMsg)) return;

            // 4. Send API Request
            try {
                const response = await fetch('api/book_room_multi.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(bookings)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Booking Successful!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            } catch (e) {
                console.error(e);
                showToast('Network error occurred', 'error');
            }
        }
    </script>
</body>
</html>
