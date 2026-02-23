<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../middleware/role_check.php';

// Access control: PE Admin ONLY
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pe_admin') {
    header('Location: ../login.php');
    exit;
}

$current_page = 'manage_fields';

// Fetch All Fields
$stmt = $pdo->query("SELECT * FROM sports_fields ORDER BY created_at DESC");
$fields = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fields - VarsityHub</title>
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
                        <a href="admin_dashboard.php?status=pending" class="block px-4 py-2 rounded-md hover:bg-gray-50 text-gray-700">Requests</a>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Facilities</div>
                    <div class="space-y-1">
                        <a href="manage_fields.php" class="block px-4 py-2 rounded-md bg-primary/10 text-primary font-medium">Manage Fields</a>
                    </div>
                </div>
            
                <a href="../logout.php" class="block px-4 py-2 rounded-md text-red-600 hover:bg-red-50 mt-8">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto h-screen">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold">Manage Sports Fields</h2>
                    <p class="text-gray-500 text-sm">Add and view available sports facilities</p>
                </div>
                <button onclick="openAddFieldModal()" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90 transition flex items-center gap-2 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Add New Field
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3">Field Name</th>
                            <th class="px-6 py-3">Location</th>
                            <th class="px-6 py-3">Created At</th>
                            <th class="px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($fields)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No fields found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fields as $field): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($field['name']); ?></td>
                                <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($field['location']); ?></td>
                                <td class="px-6 py-4 text-gray-500 text-xs">
                                    <?php echo date('M d, Y', strtotime($field['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick="deleteField(<?php echo $field['id']; ?>, '<?php echo addslashes($field['name']); ?>')" class="text-red-600 hover:text-red-900 font-medium text-xs border border-red-200 px-2 py-1 rounded hover:bg-red-50 transition">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Field Modal -->
    <div id="add-field-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 transition-opacity cursor-pointer" onclick="closeAddFieldModal()"></div>
        <div class="relative z-10 flex min-h-screen items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 transform transition-all">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Add New Sports Field</h3>
                <form onsubmit="submitAddField(event)" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>
                        <input type="text" id="field-name" required class="w-full border-gray-300 rounded-md shadow-sm border p-2 text-sm focus:ring-primary focus:border-primary" placeholder="e.g. West Field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input type="text" id="field-location" required class="w-full border-gray-300 rounded-md shadow-sm border p-2 text-sm focus:ring-primary focus:border-primary" placeholder="e.g. Near Gymnasium">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeAddFieldModal()" class="text-gray-600 hover:text-gray-800 text-sm font-medium">Cancel</button>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary/90 transition shadow-sm">Save Field</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
        function openAddFieldModal() {
            document.getElementById('add-field-modal').classList.remove('hidden');
        }

        function closeAddFieldModal() {
            document.getElementById('add-field-modal').classList.add('hidden');
        }

        async function submitAddField(e) {
            e.preventDefault();
            const name = document.getElementById('field-name').value;
            const location = document.getElementById('field-location').value;

            try {
                const response = await fetch('../api/sports_add_field.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, location })
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

        async function deleteField(id, name) {
            if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) return;

            try {
                const response = await fetch('../api/sports_delete_field.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ field_id: id })
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
