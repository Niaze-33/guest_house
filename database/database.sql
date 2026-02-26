CREATE DATABASE IF NOT EXISTS guest_house;
USE guest_house;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'register', 'teacher', 'staff', 'student') DEFAULT 'student',
    gender ENUM('male', 'female', 'other'),
    designation VARCHAR(100),
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Guest Houses Table
CREATE TABLE IF NOT EXISTS guest_houses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    district VARCHAR(100),
    description TEXT,
    total_rooms INT DEFAULT 0,
    available_rooms INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Amenities Table
CREATE TABLE IF NOT EXISTS guest_house_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_house_id INT,
    amenity VARCHAR(100),
    FOREIGN KEY (guest_house_id) REFERENCES guest_houses(id) ON DELETE CASCADE
);

-- Rooms Table
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_house_id INT,
    room_number VARCHAR(50) NOT NULL,
    type ENUM('Single', 'Double', 'Triple') NOT NULL,
    status ENUM('available', 'partial', 'full', 'maintenance') DEFAULT 'available',
    FOREIGN KEY (guest_house_id) REFERENCES guest_houses(id) ON DELETE CASCADE
);

-- Beds Table
CREATE TABLE IF NOT EXISTS beds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    bed_number VARCHAR(50) NOT NULL,
    is_booked BOOLEAN DEFAULT FALSE,
    booked_by_gender ENUM('male', 'female') NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    room_id INT,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'checked_out') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Booking Beds (Many-to-Many for beds in a booking)
CREATE TABLE IF NOT EXISTS booking_beds (
    booking_id INT,
    bed_id INT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (bed_id) REFERENCES beds(id)
);

-- Conference Rooms Table
CREATE TABLE IF NOT EXISTS conference_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    capacity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Conference Bookings Table
CREATE TABLE IF NOT EXISTS conference_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    conference_room_id INT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (conference_room_id) REFERENCES conference_rooms(id)
);

-- Insert Seed Data (Guest Houses)
INSERT INTO guest_houses (name, district, description, total_rooms, available_rooms) VALUES 
('Dhaka Guest House', 'Dhaka District', 'Located in the heart of Dhaka, convenient for city-based activities and meetings.', 12, 8),
('Barishal Guest House', 'Barishal District', 'Peaceful location in Barishal, ideal for academic visits and regional programs.', 8, 5);

INSERT INTO guest_house_amenities (guest_house_id, amenity) VALUES 
(1, 'Air Conditioning'), (1, 'Wi-Fi'), (1, 'Attached Bathroom'), (1, '24/7 Security'),
(2, 'Air Conditioning'), (2, 'Wi-Fi'), (2, 'Attached Bathroom'), (2, 'Garden View');

-- Insert Seed Data (Conference Rooms)
INSERT INTO conference_rooms (name, location, capacity) VALUES 
('Jibonando Das Hall', 'Main Building, 1st Floor', 100),
('Kirtonkhola Hall', 'Academic Block B', 80),
('Video Conference Room', 'Admin Building', 30);
