<?php require_once 'includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VarsityHub - Campus Services</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: hsl(222.2 47.4% 11.2%);
            --color-primary-foreground: hsl(210 40% 98%);
            --color-accent: hsl(217.2 91.2% 59.8%);
            --color-accent-foreground: hsl(222.2 47.4% 11.2%);
        }
        .hero-bg {
            background-image: linear-gradient(rgba(30, 41, 59, 0.85), rgba(30, 41, 59, 0.7)), url('img.jpg');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2">
                    <!-- Icon -->
                    <div class="bg-primary/10 p-2 rounded-lg text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <span class="font-bold text-xl text-primary tracking-tight">VarsityHub</span>
                </div>
                <div class="hidden md:flex space-x-8 text-sm font-medium text-gray-600">
                    <a href="#" class="text-primary hover:text-primary transition">Home</a>
                    <a href="#services" class="hover:text-primary transition">Services</a>
                    <a href="#" class="hover:text-primary transition">About</a>
                    <a href="#foot" class="hover:text-primary transition">Contact</a>
                </div>
                <div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium text-gray-600 hidden md:block">
                                Hello, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            </span>
                            <a href="logout.php" class="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-lg font-medium hover:bg-red-100 transition shadow-sm text-sm">
                                Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="bg-primary text-primary-foreground px-5 py-2 rounded-lg font-medium hover:bg-primary/90 transition shadow-sm">
                            Login
                        </a>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'pe_admin'): ?>
                        <a href="sports/admin_dashboard.php" class="ml-4 text-sm font-medium text-primary hover:underline">
                            PE Board
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative hero-bg h-[600px] flex items-center text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="max-w-2xl space-y-6">
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight leading-tight">
                    Welcome to <span class="text-yellow-400">VarsityHub</span>
                </h1>
                <p class="text-lg md:text-xl text-gray-200">
                    Your central portal for campus resources. Book guest houses, reserve conference rooms, schedule fields, and manage appointments easily.
                </p>
                <div class="flex gap-4 pt-4">
                    <a href="#services" class="bg-white text-primary px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition shadow-lg flex items-center gap-2">
                        Get Started
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </a>
                    <a href="#" class="bg-primary/50 backdrop-blur-sm border border-white/20 text-white px-6 py-3 rounded-lg font-medium hover:bg-primary/70 transition">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Section -->
    <div id="services" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl font-bold text-gray-900">Campus Services</h2>
                <p class="mt-4 text-gray-600">Access all university facilities and resources from a single dashboard. Select a service to verify availability and book.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <!-- Card 1: Guest House (Active) -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition border border-gray-100 overflow-hidden group">
                    <div class="h-48 bg-gray-200 relative overflow-hidden">
                        <img src="gust.webp" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="Guest House">
                        <div class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition"></div>
                    </div>
                    <div class="p-6">
                        <div class="bg-blue-50 w-10 h-10 rounded-lg flex items-center justify-center text-blue-600 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Guest House</h3>
                        <p class="text-sm text-gray-500 mb-6">Comfortable accommodation for visiting faculty, researchers, and university guests.</p>
                        
                        <?php 
                        $ghBookLink = 'login.php';
                        $ghOnClick = '';
                        
                        if (isset($_SESSION['user_id'])) {
                            if ($_SESSION['role'] === 'student') {
                                $ghBookLink = 'javascript:void(0);';
                                $ghOnClick = 'onclick="document.getElementById(\'access-denied-modal\').classList.remove(\'hidden\')"';
                            } else {
                                $ghBookLink = 'guest-houses.php';
                            }
                        }
                        ?>
                        
                        <a href="<?php echo $ghBookLink; ?>" <?php echo $ghOnClick; ?> class="block w-full text-center py-2.5 rounded-lg border border-primary text-primary font-medium hover:bg-primary hover:text-white transition group-hover:shadow-lg">
                            Book a Room
                        </a>
                    </div>
                </div>

                <!-- Card 2: Conference Rooms -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition border border-gray-100 overflow-hidden group">
                    <div class="h-48 bg-gray-200 relative overflow-hidden">
                         <img src="video.jpg" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="Conference Room">
                    </div>
                    <div class="p-6">
                        <div class="bg-purple-50 w-10 h-10 rounded-lg flex items-center justify-center text-purple-600 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Conference Rooms</h3>
                        <p class="text-sm text-gray-500 mb-6">State-of-the-art meeting spaces equipped with modern AV technology.</p>
                        <a href="conference/index.php" class="block w-full text-center py-2.5 rounded-lg border border-primary text-primary font-medium hover:bg-primary hover:text-white transition group-hover:shadow-lg">
                            Book Conference Room
                        </a>
                    </div>
                </div>

                <!-- Card 3: Sports Field -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition border border-gray-100 overflow-hidden group">
                    <div class="h-48 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="Sports Field">
                    </div>
                    <div class="p-6">
                        <div class="bg-green-50 w-10 h-10 rounded-lg flex items-center justify-center text-green-600 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Sports Facilities</h3>
                        <p class="text-sm text-gray-500 mb-6">Reserve the university stadium, football ground, or indoor courts for events.</p>
                        <?php 
                        $sportsLink = 'sports/index.php';
                        $sportsText = 'Book Sports Field';
                        if (isset($_SESSION['role']) && $_SESSION['role'] === 'pe_admin') {
                            $sportsLink = 'sports/admin_dashboard.php';
                            $sportsText = 'Manage Requests';
                        }
                        ?>
                        <a href="<?php echo $sportsLink; ?>" class="block w-full text-center py-2.5 rounded-lg border border-primary text-primary font-medium hover:bg-primary hover:text-white transition group-hover:shadow-lg">
                            <?php echo $sportsText; ?>
                        </a>
                    </div>
                </div>

                <!-- Card 4: Appointments -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition border border-gray-100 overflow-hidden group">
                    <div class="h-48 bg-gray-200 relative overflow-hidden">
                        <img src="sir.avif" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="Appointment">
                    </div>
                    <div class="p-6">
                        <div class="bg-orange-50 w-10 h-10 rounded-lg flex items-center justify-center text-orange-600 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Appointments</h3>
                        <p class="text-sm text-gray-500 mb-6">Schedule consultations and meetings with professors and department heads.</p>
                        <button disabled class="block w-full text-center py-2.5 rounded-lg border border-gray-200 text-gray-400 font-medium cursor-not-allowed">
                            Coming Soon
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-primary text-gray-300 py-12 border-t border-white/10">
        <div id="foot" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                        <span class="font-bold text-lg">VarsityHub</span>
                    </div>
                    <p class="text-sm text-gray-400 leading-relaxed">
                        Smooth digital solutions for university booking, scheduling, and management.
                    </p>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition">Home</a></li>
                        <li><a href="#services" class="hover:text-white transition">Services</a></li>
                        <li><a href="#" class="hover:text-white transition">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Kuarchor,Rupatoli,Barishal
                        </li>
                        <li class="flex items-center gap-2">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            +880187XX-XXX2
                        </li>
                        <li class="flex items-center gap-2">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            support@varsityhub.edu
                        </li>
                    </ul>
                </div>
                <div>
                     <h3 class="text-white font-semibold mb-4">Follow Us</h3>
                     <div class="flex gap-4">
                         <a href="#" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition">
                             <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                         </a>
                         <a href="#" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition">
                             <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                         </a>
                     </div>
                </div>
            </div>
            <div class="border-t border-white/10 pt-8 text-center text-sm text-gray-500">
                &copy; 2026 VarsityHub. All rights reserved. Built for University Excellence.
            </div>
        </div>
    </footer>

    <!-- Access Denied Modal -->
    <div id="access-denied-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity cursor-pointer" onclick="document.getElementById('access-denied-modal').classList.add('hidden')"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Access Denied</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Students are not authorized to book Guest House rooms directly. 
                                        Please contact your department head or the administration office if you require accommodation.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" onclick="document.getElementById('access-denied-modal').classList.add('hidden')">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
