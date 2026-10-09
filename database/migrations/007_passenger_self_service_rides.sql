-- Link passenger-owned accounts to their active trip and support self-service drop-off.

ALTER TABLE trip_passenger_fares
    ADD COLUMN passenger_id INT NULL AFTER driver_id,
    ADD COLUMN status ENUM('on_board', 'dropped_off') NOT NULL DEFAULT 'dropped_off' AFTER fare,
    ADD COLUMN dropped_off_at TIMESTAMP NULL DEFAULT NULL AFTER boarded_at,
    ADD UNIQUE KEY uq_trip_passenger_fares_trip_passenger (trip_id, passenger_id),
    ADD KEY idx_trip_passenger_fares_passenger_status (passenger_id, status),
    ADD CONSTRAINT fk_trip_passenger_fares_passenger FOREIGN KEY (passenger_id)
        REFERENCES passengers (passengers_id) ON UPDATE CASCADE ON DELETE RESTRICT;
