CREATE DATABASE doctor_booking;
USE doctor_booking;

-- 1. Users Table (for all users)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role ENUM('patient', 'doctor', 'admin') NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE
);

-- 2. Doctor Details
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    specialization VARCHAR(100),
    qualification TEXT,
    experience_years INT,
    consultation_fee DECIMAL(10,2),
    bio TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Specializations
CREATE TABLE specializations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT
);

-- 4. Doctor Specializations (Many-to-Many)
CREATE TABLE doctor_specializations (
    doctor_id INT,
    specialization_id INT,
    PRIMARY KEY (doctor_id, specialization_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (specialization_id) REFERENCES specializations(id) ON DELETE CASCADE
);

-- 5. Availability Slots
CREATE TABLE availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 30, -- in minutes
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- 6. Appointments
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    symptoms TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_booking (doctor_id, appointment_date, appointment_time)
);

-- 7. Reviews
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT UNIQUE,
    patient_id INT,
    doctor_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- Add admin user (password: admin123)
INSERT INTO users (role, email, password, full_name, is_verified, is_active) 
VALUES ('admin', 'admin@hospital.com', '$2y$10$z8e5jK9m2X3nL1pQ7r.S/.V9oY8u6wA5bC2dE4fG7hI0jK1lM3nO5p', 'System Admin', 1, 1);

-- Add specializations
INSERT INTO specializations (name, description) VALUES
('Cardiology', 'Heart and cardiovascular diseases'),
('Dermatology', 'Skin conditions and treatments'),
('Neurology', 'Brain and nervous system disorders'),
('Orthopedics', 'Bone and joint disorders'),
('Pediatrics', 'Child health and development'),
('General Practice', 'General medical consultation');

-- Add sample doctors (password: doctor123)
INSERT INTO users (role, email, password, full_name, phone, address, is_verified, is_active) VALUES
('doctor', 'dr.diggle@hospital.com', '$2y$10$z8e5jK9m2X3nL1pQ7r.S/.V9oY8u6wA5bC2dE4fG7hI0jK1lM3nO5p', 'John Diggle', '9876543210', '123 Medical Plaza, Douala', 1, 1),
('doctor', 'dr.joseph@hospital.com', '$2y$10$z8e5jK9m2X3nL1pQ7r.S/.V9oY8u6wA5bC2dE4fG7hI0jK1lM3nO5p', 'Dr Joseph Aristotde', '9876543211', '456 Health Center, Yaoundé', 1, 1),
('doctor', 'dr.nahbilla@hospital.com', '$2y$10$z8e5jK9m2X3nL1pQ7r.S/.V9oY8u6wA5bC2dE4fG7hI0jK1lM3nO5p', 'Dr Nahbilla Nwanah', '9876543212', '789 Clinic Street, Kumba', 1, 1),
('doctor', 'dr.yann@hospital.com', '$2y$10$z8e5jK9m2X3nL1pQ7r.S/.V9oY8u6wA5bC2dE4fG7hI0jK1lM3nO5p', 'Dr Yann Brel', '9876543213', '321 Care Hospital, Buea', 1, 1),
('doctor', 'dr.thierry@hospital.com', '$2y$10$z8e5jK9m2X3nL1pQ7r.S/.V9oY8u6wA5bC2dE4fG7hI0jK1lM3nO5p', 'Dr Thierry Divine', '9876543214', '654 Medical Center, Bamenda', 1, 1),
('doctor', 'dr.miriam@hospital.com', '$2y$10$z8e5jK9m2X3nL1pQ7r.S/.V9oY8u6wA5bC2dE4fG7hI0jK1lM3nO5p', 'Dr Miriam Andra', '9876543215', '987 Health Clinic, Limbe', 1, 1);

-- Add doctor details
INSERT INTO doctors (user_id, specialization, qualification, experience_years, consultation_fee, bio) VALUES
(2, 'Cardiology', 'MBBS, MD Cardiology', 15, 250000.00, 'Expert cardiologist with 15 years of experience in treating heart diseases.'),
(3, 'Dermatology', 'MBBS, MD Dermatology', 12, 200000.00, 'Specializing in skin treatments and cosmetic procedures.'),
(4, 'Neurology', 'MBBS, MD Neurology', 18, 275000.00, 'Expert in neurological disorders and brain health.'),
(5, 'Pediatrics', 'MBBS, MD Pediatrics', 10, 175000.00, 'Dedicated to child health and development.'),
(6, 'Orthopedics', 'MBBS, MS Orthopedics', 20, 300000.00, 'Specialist in bone, joint and sports medicine.'),
(7, 'General Practice', 'MBBS, General Practice', 8, 150000.00, 'General medical consultation for all age groups.');

-- Add doctor specializations
INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES
(1, 1),  -- Dr. Sharma - Cardiology
(2, 2),  -- Dr. Patel - Dermatology
(3, 3),  -- Dr. Singh - Neurology
(4, 5),  -- Dr. Verma - Pediatrics
(5, 4),  -- Dr. Gupta - Orthopedics
(6, 6);  -- Dr. Khan - General Practice

-- Add sample availability for doctors (9 AM to 5 PM, 30 min slots)
INSERT INTO availability (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES
(1, 'Monday', '09:00:00', '17:00:00', 30),
(1, 'Tuesday', '09:00:00', '17:00:00', 30),
(1, 'Wednesday', '09:00:00', '17:00:00', 30),
(1, 'Thursday', '09:00:00', '17:00:00', 30),
(1, 'Friday', '09:00:00', '17:00:00', 30),
(1, 'Saturday', '09:00:00', '13:00:00', 30),
(2, 'Monday', '10:00:00', '18:00:00', 30),
(2, 'Tuesday', '10:00:00', '18:00:00', 30),
(2, 'Wednesday', '10:00:00', '18:00:00', 30),
(2, 'Thursday', '10:00:00', '18:00:00', 30),
(2, 'Friday', '10:00:00', '18:00:00', 30),
(2, 'Saturday', '10:00:00', '14:00:00', 30),
(3, 'Monday', '08:00:00', '16:00:00', 30),
(3, 'Tuesday', '08:00:00', '16:00:00', 30),
(3, 'Wednesday', '08:00:00', '16:00:00', 30),
(3, 'Thursday', '08:00:00', '16:00:00', 30),
(3, 'Friday', '08:00:00', '16:00:00', 30),
(3, 'Saturday', '08:00:00', '12:00:00', 30),
(4, 'Monday', '09:00:00', '17:00:00', 30),
(4, 'Tuesday', '09:00:00', '17:00:00', 30),
(4, 'Wednesday', '09:00:00', '17:00:00', 30),
(4, 'Thursday', '09:00:00', '17:00:00', 30),
(4, 'Friday', '09:00:00', '17:00:00', 30),
(4, 'Saturday', '09:00:00', '13:00:00', 30),
(5, 'Monday', '09:00:00', '17:00:00', 30),
(5, 'Tuesday', '09:00:00', '17:00:00', 30),
(5, 'Wednesday', '09:00:00', '17:00:00', 30),
(5, 'Thursday', '09:00:00', '17:00:00', 30),
(5, 'Friday', '09:00:00', '17:00:00', 30),
(5, 'Saturday', '09:00:00', '13:00:00', 30),
(6, 'Monday', '09:00:00', '17:00:00', 30),
(6, 'Tuesday', '09:00:00', '17:00:00', 30),
(6, 'Wednesday', '09:00:00', '17:00:00', 30),
(6, 'Thursday', '09:00:00', '17:00:00', 30),
(6, 'Friday', '09:00:00', '17:00:00', 30),
(6, 'Saturday', '09:00:00', '13:00:00', 30);