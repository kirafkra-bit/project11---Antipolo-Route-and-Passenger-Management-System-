-- Add passenger complaints and admin follow-up without changing existing records.

CREATE TABLE IF NOT EXISTS complaints (
    id INT NOT NULL AUTO_INCREMENT,
    passenger_id INT NOT NULL,
    trip_id INT NULL,
    category VARCHAR(40) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('submitted','in_review','resolved','rejected') NOT NULL DEFAULT 'submitted',
    admin_response TEXT NULL,
    handled_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_complaints_passenger (passenger_id),
    KEY idx_complaints_trip (trip_id),
    KEY idx_complaints_status (status),
    KEY idx_complaints_handler (handled_by),
    CONSTRAINT fk_complaints_passenger FOREIGN KEY (passenger_id)
        REFERENCES passengers (passengers_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_complaints_trip FOREIGN KEY (trip_id)
        REFERENCES trips (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_complaints_handler FOREIGN KEY (handled_by)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
