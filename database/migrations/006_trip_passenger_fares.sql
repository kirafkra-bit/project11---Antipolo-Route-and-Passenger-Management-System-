-- Persist individual passenger journeys and calculator-based fares per driver trip.

ALTER TABLE trips
    ADD COLUMN vehicle_type ENUM('traditional', 'modern') NOT NULL DEFAULT 'traditional' AFTER jeepney_id;

CREATE TABLE IF NOT EXISTS trip_passenger_fares (
    id INT NOT NULL AUTO_INCREMENT,
    trip_id INT NOT NULL,
    driver_id INT NOT NULL,
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
    boarded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_trip_passenger_fares_trip (trip_id, boarded_at),
    KEY idx_trip_passenger_fares_driver_time (driver_id, boarded_at),
    CONSTRAINT fk_trip_passenger_fares_trip FOREIGN KEY (trip_id)
        REFERENCES trips (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_trip_passenger_fares_driver FOREIGN KEY (driver_id)
        REFERENCES drivers (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
