-- PoloNav schema (already applied on the school/local MySQL).
-- This file is a copy of the finished database. Do not invent extra tables here.

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
    license_number VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
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
    PRIMARY KEY (id)
);

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
    drop_off VARCHAR(255) NOT NULL,
    pickup VARCHAR(255) NOT NULL,
    number_of_passengers INT NOT NULL DEFAULT 0,
    passengers_id INT NOT NULL,
    jeepney_id INT NOT NULL,
    drivers_id INT NOT NULL,
    PRIMARY KEY (id),
    KEY idx_trips_passengers_id (passengers_id),
    KEY idx_trips_jeepney_id (jeepney_id),
    KEY idx_trips_drivers_id (drivers_id),
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
        ON UPDATE CASCADE
);
