<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

require_once 'middleware/role_check.php';

requireGuestHouseAccess();

try {
    $stmt = $pdo->query("
        SELECT 
            gh.id, gh.name, gh.district, gh.description,
            (SELECT COUNT(*) FROM rooms r WHERE r.guest_house_id = gh.id) as total_rooms,
            (SELECT COUNT(*) FROM rooms r WHERE r.guest_house_id = gh.id AND r.status = 'available') as available_rooms
        FROM guest_houses gh
    ");
    $guestHouses = $stmt->fetchAll();

    foreach ($guestHouses as &$house) {
        $stmtAmenity = $pdo->prepare("SELECT amenity FROM guest_house_amenities WHERE guest_house_id = ?");
        $stmtAmenity->execute([$house['id']]);
        $house['amenities'] = $stmtAmenity->fetchAll(PDO::FETCH_COLUMN);
    }
    unset($house); 
} catch (PDOException $e) {
    die("Error fetching guest houses: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Houses - University Booking</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: hsl(222.2 47.4% 11.2%);
            --color-primary-foreground: hsl(210 40% 98%);
            --color-background: hsl(0 0% 100%);
            --color-foreground: hsl(222.2 47.4% 11.2%);
            --color-muted: hsl(210 40% 96.1%);
            --color-muted-foreground: hsl(215.4 16.3% 46.9%);
            --color-card: hsl(0 0% 100%);
            --color-border: hsl(214.3 31.8% 91.4%);
        }
    </style>
</head>
<body class="bg-background text-foreground font-sans">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-primary text-primary-foreground py-4 px-6 flex justify-between items-center shadow-md">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <h1 class="text-xl font-bold">University Guest House</h1>
            </div>
            <div class="flex items-center gap-4">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/index.php" class="text-sm font-medium hover:underline text-blue-200">Admin Panel</a>
                <?php endif; ?>
                <a href="dashboard.php" class="text-sm font-medium hover:underline">Dashboard</a>
                <a href="logout.php" class="text-sm bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition">Logout</a>
            </div>
        </header>
        
        <main class="flex-1 p-6 md:p-10">
            <div class="max-w-7xl mx-auto space-y-8">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Guest Houses</h1>
                    <p class="text-muted-foreground mt-2 text-lg">
                        Select a guest house to view available rooms and make a booking
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <?php foreach ($guestHouses as $house): ?>
                    <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <div class="bg-primary h-24 relative flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-foreground/30"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </div>
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-2xl font-semibold leading-none tracking-tight"><?php echo htmlspecialchars($house['name']); ?></h3>
                                    <div class="flex items-center gap-1 mt-2 text-muted-foreground">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <span class="text-sm"><?php echo htmlspecialchars($house['district']); ?></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-3xl font-bold text-primary block"><?php echo $house['available_rooms']; ?></span>
                                    <span class="text-xs text-muted-foreground">rooms available</span>
                                </div>
                            </div>
                            
                            <p class="text-sm text-muted-foreground mb-6 leading-relaxed">
                                <?php echo htmlspecialchars($house['description']); ?>
                            </p>

                            <div class="flex flex-wrap gap-2 mb-6">
                                <?php foreach ($house['amenities'] as $amenity): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-muted text-muted-foreground">
                                    <?php echo htmlspecialchars($amenity); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>

                            <div class="border-t border-border pt-4 flex items-center justify-between">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/></svg>
                                    <span><?php echo $house['total_rooms']; ?> total rooms</span>
                                </div>
                                <a href="rooms.php?house_id=<?php echo $house['id']; ?>" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-4 py-2">
                                    View Rooms
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
