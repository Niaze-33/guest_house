<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../middleware/role_check.php';

// Enforce access control
requireConferenceAccess();

$current_page = 'conference';
$user_id = $_SESSION['user_id'];

// Fetch Conference Rooms
$stmt = $pdo->query("SELECT * FROM conference_rooms ORDER BY name ASC");
$rooms = $stmt->fetchAll();

// Handle Search / Filter if needed (for later)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conference Room Booking - VarsityHub</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: hsl(222.2 47.4% 11.2%);
            --color-primary-foreground: hsl(210 40% 98%);
            --color-background: hsl(0 0% 100%);
            --color-foreground: hsl(222.2 47.4% 11.2%);
            --color-border: hsl(214.3 31.8% 91.4%);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../index.php?v=2" class="flex items-center gap-2">
                         <div class="bg-primary/10 p-2 rounded-lg text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <span class="font-bold text-xl text-primary">VarsityHub Home</span>
                    </a>
                </div>
                <div class="flex items-center gap-4">
                     <span class="text-sm font-medium text-gray-600">
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full ml-1 uppercase"><?php echo $_SESSION['role']; ?></span>
                    </span>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Conference Rooms</h1>
                <p class="mt-2 text-gray-600">Select a venue for your event or meeting.</p>
            </div>
            <div>
                <a href="history.php" class="text-primary hover:underline font-medium">My Bookings</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($rooms as $room): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 h-32 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($room['name']); ?></h3>
                    <div class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php echo htmlspecialchars($room['location']); ?>
                    </div>
                    <div class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Capacity: <?php echo htmlspecialchars($room['capacity']); ?>
                    </div>

                    <div class="mt-6">
                        <button onclick="openBookingModal(<?php echo htmlspecialchars(json_encode($room)); ?>)" 
                                class="w-full bg-primary text-white py-2 rounded-lg font-medium hover:bg-primary/90 transition">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Booking Modal -->
    <div id="booking-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm cursor-pointer" onclick="closeModal()"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-white rounded-xl shadow-2xl p-6 h-[80vh] flex flex-col">
            <h2 class="text-2xl font-bold mb-4" id="modal-title">Book Room</h2>
            
            <div class="flex-1 overflow-y-auto pr-2">
                <form id="booking-form" onsubmit="submitBooking(event)">
                    <input type="hidden" name="room_id" id="modal-room-id">
                    
                    <div id="availability-loading" class="text-center py-8">
                        <svg class="animate-spin h-8 w-8 text-primary mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">Loading availability...</p>
                    </div>

                    <div id="availability-container" class="hidden">
                        <p class="text-sm text-gray-500 mb-4">Select slots for the upcoming 10 days. Morning: 9am-12pm, Afternoon: 2pm-5pm.</p>
                        
                        <table class="w-full text-sm text-left border-collapse">
                            <thead class="bg-gray-50 text-gray-600 font-medium border-b sticky top-0">
                                <tr>
                                    <th class="px-4 py-2">Date</th>
                                    <th class="px-4 py-2 text-center">Morning</th>
                                    <th class="px-4 py-2 text-center">Afternoon</th>
                                </tr>
                            </thead>
                            <tbody id="availability-table-body" class="divide-y divide-gray-100">
                                <!-- Rows injected by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Book Selected Slots</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/toast.js"></script>
    <script>
        let currentRoomId = 0;

        function openBookingModal(room) {
            currentRoomId = room.id;
            document.getElementById('modal-title').innerText = 'Book ' + room.name;
            document.getElementById('modal-room-id').value = room.id;
            document.getElementById('booking-modal').classList.remove('hidden');
            
            fetchAvailability(room.id);
        }

        function closeModal() {
            document.getElementById('booking-modal').classList.add('hidden');
            document.getElementById('availability-table-body').innerHTML = '';
            document.getElementById('availability-loading').classList.remove('hidden');
            document.getElementById('availability-container').classList.add('hidden');
        }

        async function fetchAvailability(roomId) {
            try {
                const res = await fetch(`../api/get_conference_availability.php?room_id=${roomId}`);
                const data = await res.json();
                
                if (data.success) {
                    renderAvailability(data.data.availability);
                } else {
                    showToast('Failed to load availability', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Error loading availability', 'error');
            }
        }

        function renderAvailability(availability) {
            const tbody = document.getElementById('availability-table-body');
            tbody.innerHTML = '';
            
            // availability is an object: { "2024-01-30": { "morning": true, "afternoon": false }, ... }
            Object.keys(availability).forEach(date => {
                const dayData = availability[date];
                const dateObj = new Date(date);
                const dateString = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                
                tr.innerHTML = `
                    <td class="px-4 py-3 font-medium text-gray-900">${dateString}</td>
                    <td class="px-4 py-3 text-center">
                        ${dayData.morning 
                            ? `<input type="checkbox" name="slots[]" value="${date}|morning" class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary cursor-pointer">`
                            : `<span class="text-xs text-red-500 font-medium bg-red-50 px-2 py-1 rounded">Booked</span>`
                        }
                    </td>
                    <td class="px-4 py-3 text-center">
                        ${dayData.afternoon 
                            ? `<input type="checkbox" name="slots[]" value="${date}|afternoon" class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary cursor-pointer">`
                            : `<span class="text-xs text-red-500 font-medium bg-red-50 px-2 py-1 rounded">Booked</span>`
                        }
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('availability-loading').classList.add('hidden');
            document.getElementById('availability-container').classList.remove('hidden');
        }

        async function submitBooking(e) {
            e.preventDefault();
            
            // Collect selected slots
            const checkboxes = document.querySelectorAll('input[name="slots[]"]:checked');
            const bookings = Array.from(checkboxes).map(cb => {
                const [date, shift] = cb.value.split('|');
                return { date, shift };
            });

            if (bookings.length === 0) {
                showToast('Please select at least one slot.', 'warning');
                return;
            }

            try {
                const response = await fetch('../api/book_conference.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        room_id: currentRoomId,
                        bookings: bookings
                    })
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Something went wrong', 'error');
            }
        }
    </script>

</body>
</html>
