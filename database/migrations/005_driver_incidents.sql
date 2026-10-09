-- Store driver-reported incidents for global visibility across driver accounts.

CREATE TABLE IF NOT EXISTS incident_reports (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
