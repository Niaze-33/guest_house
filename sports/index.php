<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../middleware/role_check.php';

// Access control
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Redirect PE Admin to their dashboard (They cannot book)
if ($_SESSION['role'] === 'pe_admin') {
    header('Location: admin_dashboard.php');
    exit;
}

$current_page = 'sports';
$user_id = $_SESSION['user_id'];

// Fetch Sports Fields
try {
    $stmt = $pdo->query("SELECT * FROM sports_fields ORDER BY name ASC");
    $fields = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching fields: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Facilities - VarsityHub</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: hsl(222.2 47.4% 11.2%);
            --color-primary-foreground: hsl(210 40% 98%);
            --color-background: hsl(0 0% 100%);
            --color-foreground: hsl(222.2 47.4% 11.2%);
            --color-border: hsl(214.3 31.8% 91.4%);
            --color-success: hsl(142.1 76.2% 36.3%);
            --color-warning: hsl(38 92% 50%);
            --color-danger: hsl(0 84.2% 60.2%);
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="font-bold text-xl text-primary">VarsityHub Sports</span>
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
                <h1 class="text-3xl font-bold text-gray-900">Sports Fields</h1>
                <p class="mt-2 text-gray-600">Select a field to book for practice or matches.</p>
            </div>
            <div>
                <a href="my_bookings.php" class="text-primary hover:underline font-medium">My Bookings</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($fields as $field): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition flex flex-col">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 h-40 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($field['name']); ?></h3>
                    <div class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php echo htmlspecialchars($field['location']); ?>
                    </div>
                    
                    <div class="mt-6 mt-auto">
                        <button onclick="openBookingModal(<?php echo htmlspecialchars(json_encode($field)); ?>)" 
                                class="w-full bg-primary text-white py-2 rounded-lg font-medium hover:bg-primary/90 transition shadow-sm">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Booking Modal -->
    <div id="booking-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500/75 transition-opacity" onclick="closeModal()"></div>
        
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl max-h-[90vh] flex flex-col">
                    
                    <!-- Modal Header -->
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">Book Field</h3>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-4 py-4 sm:p-6 overflow-y-auto">
                        <form id="booking-form" onsubmit="handleBookingSubmit(event)">
                            <input type="hidden" name="field_id" id="modal-field-id">
                            
                            <!-- Availability Loading State -->
                            <div id="availability-loading" class="text-center py-12">
                                <svg class="animate-spin h-8 w-8 text-primary mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="mt-3 text-sm text-gray-500">Checking availability...</p>
                            </div>

                            <!-- Step 1: Slot Selection -->
                            <div id="step-slots" class="hidden space-y-4">
                                <div class="bg-blue-50 text-blue-800 text-sm p-3 rounded-lg flex gap-2 items-start">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <p>Select slots for the next 10 days. Green slots are available.</p>
                                </div>

                                <div class="border rounded-lg overflow-hidden">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-gray-50 text-gray-600 font-medium border-b">
                                            <tr>
                                                <th class="px-4 py-3">Date</th>
                                                <th class="px-4 py-3 text-center">Morning (Available)</th>
                                                <th class="px-4 py-3 text-center">Afternoon (Available)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="availability-table-body" class="divide-y divide-gray-100">
                                            <!-- Rows injected by JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Step 2: Details Form -->
                            <div id="step-details" class="hidden space-y-4 pt-4 border-t border-gray-100 mt-4">
                                <h4 class="font-medium text-gray-900">Booking Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Purpose</label>
                                        <select name="purpose" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm border p-2">
                                            <option value="">Select purpose...</option>
                                            <option value="Practice">Practice</option>
                                            <option value="Match">Match</option>
                                            <option value="Tournament">Tournament</option>
                                            <option value="Event">Event</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Team / Club Name</label>
                                        <input type="text" name="team_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm border p-2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Participants (Approx)</label>
                                        <input type="number" name="participants" required min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm border p-2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                        <input type="text" name="notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm border p-2">
                                    </div>
                                </div>
                            </div>
                        
                            <!-- Footer -->
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                                <button type="submit" id="submit-btn" disabled class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Request Selected Slots
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <!-- Booking Info Modal -->
    <div id="booking-info-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="info-modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500/75 transition-opacity cursor-pointer" onclick="closeInfoModal()"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900" id="info-modal-title">Booking Details</h3>
                        <button onclick="closeInfoModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="px-4 py-4 sm:p-6 space-y-3">
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Slot</span>
                            <div class="mt-1 text-sm text-gray-900 font-medium"><span id="info-date"></span> <span id="info-slot" class="text-gray-500 font-normal capitalize"></span></div>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</span>
                            <div id="info-status" class="mt-1"></div>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Booked By</span>
                            <div class="mt-1 text-sm text-gray-900" id="info-requester"></div>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Team / Club</span>
                            <div class="mt-1 text-sm text-gray-900" id="info-team"></div>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</span>
                            <div class="mt-1 text-sm text-gray-900" id="info-purpose"></div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end">
                        <button type="button" onclick="closeInfoModal()" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm font-medium">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
        let currentFieldId = 0;
        let selectedSlots = [];

        function openBookingModal(field) {
            currentFieldId = field.id;
            document.getElementById('modal-title').innerText = 'Book ' + field.name;
            document.getElementById('modal-field-id').value = field.id;
            document.getElementById('booking-modal').classList.remove('hidden');
            
            // Reset form
            document.getElementById('booking-form').reset();
            document.getElementById('step-details').classList.add('hidden'); // Hide details initially
            selectedSlots = [];
            updateSubmitButton();

            fetchAvailability(field.id);
        }

        function closeModal() {
            document.getElementById('booking-modal').classList.add('hidden');
            document.getElementById('availability-loading').classList.remove('hidden');
            document.getElementById('step-slots').classList.add('hidden');
        }

        async function fetchAvailability(fieldId) {
            try {
                const res = await fetch(`../api/sports_fetch.php`);
                const data = await res.json();
                
                if (data.success) {
                    renderAvailability(data.bookings[fieldId] || {});
                } else {
                    showToast('Failed to load availability', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Error loading availability', 'error');
            }
        }

        function renderAvailability(fieldBookings) {
            const tbody = document.getElementById('availability-table-body');
            tbody.innerHTML = '';
            
            // Generate next 10 days
            const today = new Date();
            
            for (let i = 0; i < 10; i++) {
                const dateObj = new Date(today);
                dateObj.setDate(today.getDate() + i);
                const dateStr = dateObj.toISOString().split('T')[0]; // YYYY-MM-DD
                const dateDisplay = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });

                const morningStatus = fieldBookings[dateStr]?.morning; // 'approved', 'pending' or undefined
                const afternoonStatus = fieldBookings[dateStr]?.afternoon;

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';

                tr.innerHTML = `
                    <td class="px-4 py-3 font-medium text-gray-900">${dateDisplay}</td>
                    <td class="px-4 py-3 text-center">
                        ${renderSlot(dateStr, 'morning', morningStatus)}
                    </td>
                    <td class="px-4 py-3 text-center">
                        ${renderSlot(dateStr, 'afternoon', afternoonStatus)}
                    </td>
                `;
                tbody.appendChild(tr);
            }

            document.getElementById('availability-loading').classList.add('hidden');
            document.getElementById('step-slots').classList.remove('hidden');
        }

        function renderSlot(date, slot, slotData) {
            // Check if slotData is an object (new format) or just string/undefined
            // API now returns an object {status, purpose, team_name, requester} or nothing
            
            if (!slotData) {
                return `<label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="slots[]" value="${date}|${slot}" onchange="handleSlotChange(this)" class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="text-sm text-gray-600 capitalize">Select</span>
                        </label>`;
            }

            const status = slotData.status; // 'approved' or 'pending'
            let label = 'Booked';
            let colorClass = 'bg-red-100 text-red-800';

            if (status === 'pending') {
                label = 'Pending';
                colorClass = 'bg-yellow-100 text-yellow-800';
            }

            // Encode data for the click handler
            const dataStr = encodeURIComponent(JSON.stringify(slotData));

            return `<button type="button" onclick="showBookingInfo('${date}', '${slot}', '${dataStr}')" class="cursor-pointer inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass} hover:opacity-80 transition">
                        ${label}
                    </button>`;
        }
        
        function showBookingInfo(date, slot, dataStr) {
            const data = JSON.parse(decodeURIComponent(dataStr));
            
            document.getElementById('info-date').innerText = new Date(date).toLocaleDateString();
            document.getElementById('info-slot').innerText = '(' + slot + ')';
            document.getElementById('info-requester').innerText = data.requester || 'Unknown';
            document.getElementById('info-team').innerText = data.team_name || '-';
            document.getElementById('info-purpose').innerText = data.purpose || '-';
            
            const statusEl = document.getElementById('info-status');
            if (data.status === 'approved') {
                statusEl.innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Approved</span>';
            } else {
                statusEl.innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending Approval</span>';
            }

            document.getElementById('booking-info-modal').classList.remove('hidden');
        }

        function closeInfoModal() {
            document.getElementById('booking-info-modal').classList.add('hidden');
        }

        function handleSlotChange(checkbox) {
            if (checkbox.checked) {
                selectedSlots.push(checkbox.value);
            } else {
                selectedSlots = selectedSlots.filter(s => s !== checkbox.value);
            }

            // Show details form if at least one slot is selected
            if (selectedSlots.length > 0) {
                document.getElementById('step-details').classList.remove('hidden');
            } else {
                document.getElementById('step-details').classList.add('hidden');
            }
            updateSubmitButton();
        }

        function updateSubmitButton() {
            const btn = document.getElementById('submit-btn');
            btn.disabled = selectedSlots.length === 0;
            if (selectedSlots.length > 0) {
                 btn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                 btn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        async function handleBookingSubmit(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            
            const payload = {
                field_id: currentFieldId,
                slots: selectedSlots.map(s => {
                    const [date, slot] = s.split('|');
                    return { date, slot };
                }),
                purpose: formData.get('purpose'),
                team_name: formData.get('team_name'),
                participants: formData.get('participants'),
                notes: formData.get('notes')
            };

            try {
                const response = await fetch('../api/sports_book.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal();
                    // Optional: Refresh availability
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
