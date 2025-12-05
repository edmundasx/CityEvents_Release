-- CityEvents schema and seed data (Lithuanian region)
-- Tested with MySQL 8.x syntax

DROP DATABASE IF EXISTS cityevents;
CREATE DATABASE cityevents CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cityevents;

-- Core users
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    -- Passwords should be hashed using password_hash() in PHP
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'organizer', 'admin') NOT NULL DEFAULT 'user',
    phone VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events submitted by organizers
CREATE TABLE events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organizer_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(60) NOT NULL,
    location VARCHAR(255) NOT NULL,
    lat DECIMAL(10,6) NULL,
    lng DECIMAL(10,6) NULL,
    event_date DATETIME NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'approved', 'rejected', 'update_pending') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT NULL,
    cover_image TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_organizer FOREIGN KEY (organizer_id) REFERENCES users(id)
);
CREATE INDEX idx_events_organizer ON events (organizer_id);
CREATE INDEX idx_events_status ON events (status);
CREATE INDEX idx_events_event_date ON events (event_date);

-- Favorites (likes/save for later) per user and event
CREATE TABLE favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    tag VARCHAR(50) NOT NULL DEFAULT 'favorite',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_favorites_event FOREIGN KEY (event_id) REFERENCES events(id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE INDEX idx_favorites_user ON favorites (user_id);
CREATE INDEX idx_favorites_event ON favorites (event_id);

-- User-defined notification settings for specific events
CREATE TABLE notification_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NOT NULL,
    time_offset VARCHAR(10) NOT NULL, -- e.g., '5m', '30m', '1h', '1d'
    channels JSON NOT NULL, -- e.g., '["sms", "email"]'
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_settings_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_notification_settings_event FOREIGN KEY (event_id) REFERENCES events(id),
    UNIQUE KEY uq_user_event (user_id, event_id)
);

-- Notifications for users
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    type ENUM('user', 'organizer', 'admin') NOT NULL DEFAULT 'user',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE INDEX idx_notifications_user ON notifications (user_id);

-- Blocked users
CREATE TABLE blocked_users (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    blocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_blocked_users_user FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Seed users (Lithuanian names)
-- In a real application, passwords would be hashed. e.g., password_hash('secret', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password, role) VALUES
('Asta Vartotoja', 'asta@cityevents.lt', 'slaptas', 'user'),
('Jonas Organizatorius', 'jonas@cityevents.lt', 'organizer', 'organizer'),
('Ieva Administratore', 'ieva@cityevents.lt', 'admin', 'admin'),
('Admin Pavyzdys', 'admin@cityevents.lt', 'labai-slapta', 'admin'),
('Mantas Petrauskas', 'mantas@cityevents.lt', 'slaptas', 'user'),
('Rasa Zukauskaite', 'rasa@cityevents.lt', 'slaptas', 'user'),
('Simona Laurinaite', 'simona@cityevents.lt', 'organizer', 'organizer'),
('Tomas Jankus', 'tomas@cityevents.lt', 'slaptas', 'user'),
('Gabija Stankeviciute', 'gabija@cityevents.lt', 'slaptas', 'user'),
('Aurimas Kavaliauskas', 'aurimas@cityevents.lt', 'organizer', 'organizer'),
('Egle Simkute', 'egle@cityevents.lt', 'slaptas', 'user');

-- Seed events (dates relative to load time to mimic current/future/past cases)
INSERT INTO events (organizer_id, title, description, category, location, lat, lng, event_date, price, status, cover_image) VALUES
(2, 'Vilniaus gatves muzikos diena', 'Gyvos muzikos koncertai Gedimino prospekte', 'music', 'Vilnius, Gedimino pr. 22', 54.689159, 25.276955, DATE_ADD(NOW(), INTERVAL 5 DAY), 0.00, 'approved', 'https://images.unsplash.com/photo-1506156886591-1f54be9ea5f1?auto=format&fit=crop&w=900&q=80'),
(2, 'Neries vakarinis begimas', '10 km trasos palei upę su DJ zona finiše', 'sports', 'Vilnius, Neries krantine', 54.699921, 25.268493, DATE_ADD(NOW(), INTERVAL -3 DAY), 0.00, 'approved', 'https://images.unsplash.com/photo-1508609349937-5ec4ae374ebf?auto=format&fit=crop&w=900&q=80'),
(7, 'Kauno menų naktys', 'Instaliacijos ir performansai Laisvės alėjoje', 'arts', 'Kaunas, Laisves al. 68', 54.898521, 23.912289, DATE_ADD(NOW(), INTERVAL 12 DAY), 6.50, 'approved', 'https://images.unsplash.com/photo-1517602302552-471fe67acf66?auto=format&fit=crop&w=900&q=80'),
(10, 'Klaipedos jūros šventė', 'Laivų paradas, mugė ir koncertai prie marių', 'festival', 'Klaipeda, Kruiziniu laivu terminalas', 55.710803, 21.127880, DATE_ADD(NOW(), INTERVAL 30 DAY), 0.00, 'approved', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80'),
(7, 'Panevezio teatro vakaras', 'Jaunimo teatro trupės spektaklis', 'arts', 'Panevezys, Respublikos g. 40', 55.733472, 24.357477, DATE_ADD(NOW(), INTERVAL 2 DAY), 9.00, 'approved', 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=900&q=80'),
(10, 'Siauliu saulės džiazas', 'Vasaros džiazo koncertas skvere', 'music', 'Siauliai, Vilniaus g. 213', 55.935617, 23.313073, DATE_ADD(NOW(), INTERVAL -14 DAY), 4.00, 'approved', 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?auto=format&fit=crop&w=900&q=80'),
(2, 'Trakų pilies regata', 'Valčių lenktynės ir maisto vagonėliai Galvės ežere', 'sports', 'Trakai, Karaimu g. 53', 54.646227, 24.933012, DATE_ADD(NOW(), INTERVAL 18 DAY), 7.00, 'approved', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80'),
(7, 'Alytaus miesto piknikas', 'Šeimų piknikas su edukacinėmis dirbtuvėmis', 'family', 'Alytus, J. Basanaviciaus g. 5', 54.399222, 24.046474, DATE_ADD(NOW(), INTERVAL 7 DAY), 3.00, 'approved', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80'),
(10, 'Utenos kulinarinis savaitgalis', 'Regioninių patiekalų ragavimas ir konkursas', 'food', 'Utena, S. Dariaus ir S. Gireno g. 14', 55.493115, 25.594767, DATE_ADD(NOW(), INTERVAL 40 DAY), 5.00, 'approved', 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?auto=format&fit=crop&w=900&q=80'),
(7, 'Marijampoles kino vakarai', 'Lietuviškų filmų retrospektyva po atviru dangumi', 'arts', 'Marijampole, J. Basanaviciaus a. 4', 54.559600, 23.354116, DATE_ADD(NOW(), INTERVAL -1 DAY), 2.00, 'approved', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=900&q=80');

-- Seed favorites (10+ entries referencing existing users/events)
INSERT INTO favorites (event_id, user_id, tag, created_at) VALUES
(1, 1, 'favorite', DATE_ADD(NOW(), INTERVAL -1 DAY)),
(3, 4, 'laukiu', DATE_ADD(NOW(), INTERVAL -2 DAY)),
(2, 5, 'begu', DATE_ADD(NOW(), INTERVAL -3 DAY)),
(6, 7, 'dziazas', DATE_ADD(NOW(), INTERVAL -5 DAY)),
(4, 8, 'jura', DATE_ADD(NOW(), INTERVAL -6 DAY)),
(5, 10, 'teatras', DATE_ADD(NOW(), INTERVAL -7 DAY)),
(7, 3, 'regata', DATE_ADD(NOW(), INTERVAL -8 DAY)),
(8, 9, 'seima', DATE_ADD(NOW(), INTERVAL -9 DAY)),
(9, 6, 'maistas', DATE_ADD(NOW(), INTERVAL -10 DAY)),
(10, 2, 'kino', DATE_ADD(NOW(), INTERVAL -11 DAY));
