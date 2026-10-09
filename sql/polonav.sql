-- PoloNav fresh-install schema, synchronized with the current school/local MySQL.

CREATE DATABASE IF NOT EXISTS polonav
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE polonav;

CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'driver', 'passenger') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
);

CREATE TABLE antipolo_points (
    id INT NOT NULL AUTO_INCREMENT,
    point_key VARCHAR(80) NOT NULL,
    name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_antipolo_points_key (point_key),
    KEY idx_antipolo_points_active (is_active)
);

CREATE TABLE passengers (
    passengers_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    valid_id_file VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    users_id INT NOT NULL,
    PRIMARY KEY (passengers_id),
    KEY idx_passengers_users_id (users_id),
    CONSTRAINT fk_passengers_users
        FOREIGN KEY (users_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE drivers (
    id INT NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    license_number VARCHAR(50) NOT NULL,
    requested_route_id INT NULL,
    route_id INT NULL,
    route_assignment_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    license_image VARCHAR(255) NULL,
    other_id_image VARCHAR(255) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    verification_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    rejection_reason VARCHAR(500) NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    license_file VARCHAR(255) NULL,
    valid_id_file VARCHAR(255) NULL,
    or_cr_file VARCHAR(255) NULL,
    ctpl_file VARCHAR(255) NULL,
    mvir_file VARCHAR(255) NULL,
    emission_file VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    users_id INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_drivers_license_number (license_number),
    KEY idx_drivers_users_id (users_id),
    KEY idx_drivers_requested_route_id (requested_route_id),
    KEY idx_drivers_route_id (route_id),
    CONSTRAINT fk_drivers_users
        FOREIGN KEY (users_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE routes (
    id INT NOT NULL AUTO_INCREMENT,
    route_name VARCHAR(255) NOT NULL,
    destinations VARCHAR(255) NULL,
    distance INT NULL,
    fare INT NOT NULL DEFAULT 0,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pickup_point_id INT NULL,
    dropoff_point_id INT NULL,
    PRIMARY KEY (id),
    KEY idx_routes_pickup_point (pickup_point_id),
    KEY idx_routes_dropoff_point (dropoff_point_id),
    CONSTRAINT fk_routes_pickup_point
        FOREIGN KEY (pickup_point_id) REFERENCES antipolo_points(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_routes_dropoff_point
        FOREIGN KEY (dropoff_point_id) REFERENCES antipolo_points(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

ALTER TABLE drivers
    ADD CONSTRAINT fk_drivers_requested_route FOREIGN KEY (requested_route_id)
        REFERENCES routes(id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_drivers_assigned_route FOREIGN KEY (route_id)
        REFERENCES routes(id) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE jeepney (
    id INT NOT NULL AUTO_INCREMENT,
    plate_number VARCHAR(20) NOT NULL,
    jeepney_number INT NOT NULL,
    drivers_id INT NOT NULL,
    route_id INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_jeepney_plate_number (plate_number),
    KEY idx_jeepney_drivers_id (drivers_id),
    KEY idx_jeepney_route_id (route_id),
    CONSTRAINT fk_jeepney_drivers
        FOREIGN KEY (drivers_id) REFERENCES drivers(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_jeepney_routes
        FOREIGN KEY (route_id) REFERENCES routes(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

CREATE TABLE trips (
    id INT NOT NULL AUTO_INCREMENT,
    drop_off VARCHAR(255) NULL,
    pickup VARCHAR(255) NULL,
    number_of_passengers INT NOT NULL DEFAULT 0,
    passengers_id INT NULL,
    jeepney_id INT NOT NULL,
    vehicle_type ENUM('traditional', 'modern') NOT NULL DEFAULT 'traditional',
    route_id INT NULL,
    drivers_id INT NOT NULL,
    recorded_at TIMESTAMP NULL DEFAULT NULL,
    status ENUM('In progress', 'Pending', 'Confirmed') NOT NULL DEFAULT 'Pending',
    starting_odometer DECIMAL(10,1) NULL,
    boarded_count INT NOT NULL DEFAULT 0,
    dropped_off_count INT NOT NULL DEFAULT 0,
    confirmation_notes TEXT NULL,
    pickup_point_id INT NULL,
    dropoff_point_id INT NULL,
    PRIMARY KEY (id),
    KEY idx_trips_passengers_id (passengers_id),
    KEY idx_trips_jeepney_id (jeepney_id),
    KEY idx_trips_drivers_id (drivers_id),
    KEY idx_trips_route_id (route_id),
    KEY idx_trips_status (status),
    KEY idx_trips_pickup_point (pickup_point_id),
    KEY idx_trips_dropoff_point (dropoff_point_id),
    CONSTRAINT fk_trips_passengers
        FOREIGN KEY (passengers_id) REFERENCES passengers(passengers_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_trips_jeepney
        FOREIGN KEY (jeepney_id) REFERENCES jeepney(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_trips_drivers
        FOREIGN KEY (drivers_id) REFERENCES drivers(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_trips_route FOREIGN KEY (route_id)
        REFERENCES routes(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trips_pickup_point
        FOREIGN KEY (pickup_point_id) REFERENCES antipolo_points(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trips_dropoff_point
        FOREIGN KEY (dropoff_point_id) REFERENCES antipolo_points(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE trip_passenger_fares (
    id INT NOT NULL AUTO_INCREMENT,
    trip_id INT NOT NULL,
    driver_id INT NOT NULL,
    passenger_id INT NULL,
    vehicle_type ENUM('traditional', 'modern') NOT NULL DEFAULT 'traditional',
    passenger_type ENUM('regular', 'student', 'senior', 'pwd') NOT NULL DEFAULT 'regular',
    origin_name VARCHAR(255) NOT NULL,
    destination_name VARCHAR(255) NOT NULL,
    origin_latitude DECIMAL(10,8) NOT NULL,
    origin_longitude DECIMAL(11,8) NOT NULL,
    destination_latitude DECIMAL(10,8) NOT NULL,
    destination_longitude DECIMAL(11,8) NOT NULL,
    distance_km DECIMAL(8,2) NOT NULL,
    fare DECIMAL(8,2) NOT NULL,
    status ENUM('on_board', 'dropped_off') NOT NULL DEFAULT 'dropped_off',
    boarded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dropped_off_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_trip_passenger_fares_trip (trip_id, boarded_at),
    KEY idx_trip_passenger_fares_driver_time (driver_id, boarded_at),
    UNIQUE KEY uq_trip_passenger_fares_trip_passenger (trip_id, passenger_id),
    KEY idx_trip_passenger_fares_passenger_status (passenger_id, status),
    CONSTRAINT fk_trip_passenger_fares_trip FOREIGN KEY (trip_id)
        REFERENCES trips(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_trip_passenger_fares_driver FOREIGN KEY (driver_id)
        REFERENCES drivers(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_trip_passenger_fares_passenger FOREIGN KEY (passenger_id)
        REFERENCES passengers(passengers_id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE complaints (
    id INT NOT NULL AUTO_INCREMENT,
    passenger_id INT NOT NULL,
    trip_id INT NULL,
    category VARCHAR(40) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('submitted', 'in_review', 'resolved', 'rejected') NOT NULL DEFAULT 'submitted',
    admin_response TEXT NULL,
    handled_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_complaints_passenger (passenger_id),
    KEY idx_complaints_trip (trip_id),
    KEY idx_complaints_status (status),
    KEY idx_complaints_handler (handled_by),
    CONSTRAINT fk_complaints_handler
        FOREIGN KEY (handled_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_complaints_passenger
        FOREIGN KEY (passenger_id) REFERENCES passengers(passengers_id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_complaints_trip
        FOREIGN KEY (trip_id) REFERENCES trips(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE incident_reports (
    id INT NOT NULL AUTO_INCREMENT,
    route_id INT NOT NULL,
    driver_id INT NOT NULL,
    incident_type VARCHAR(40) NOT NULL,
    location_description VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    description TEXT NOT NULL,
    status ENUM('active', 'resolved') NOT NULL DEFAULT 'active',
    reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_incident_reports_route_status_time (route_id, status, reported_at),
    KEY idx_incident_reports_driver (driver_id),
    CONSTRAINT fk_incident_reports_route FOREIGN KEY (route_id)
        REFERENCES routes (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_incident_reports_driver FOREIGN KEY (driver_id)
        REFERENCES drivers (id) ON UPDATE CASCADE ON DELETE RESTRICT
);
