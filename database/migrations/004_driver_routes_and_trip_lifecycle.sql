ALTER TABLE drivers
    ADD COLUMN requested_route_id INT NULL AFTER license_number,
    ADD COLUMN route_id INT NULL AFTER requested_route_id,
    ADD COLUMN route_assignment_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER route_id,
    ADD KEY idx_drivers_requested_route_id (requested_route_id),
    ADD KEY idx_drivers_route_id (route_id),
    ADD CONSTRAINT fk_drivers_requested_route FOREIGN KEY (requested_route_id)
        REFERENCES routes (id) ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_drivers_assigned_route FOREIGN KEY (route_id)
        REFERENCES routes (id) ON UPDATE CASCADE ON DELETE SET NULL;

UPDATE drivers d
INNER JOIN (
    SELECT drivers_id, MIN(route_id) AS route_id
    FROM jeepney
    GROUP BY drivers_id
) assigned ON assigned.drivers_id = d.id
SET d.route_id = assigned.route_id,
    d.requested_route_id = assigned.route_id,
    d.route_assignment_status = 'approved';

ALTER TABLE trips
    MODIFY COLUMN pickup VARCHAR(255) NULL,
    MODIFY COLUMN drop_off VARCHAR(255) NULL,
    MODIFY COLUMN passengers_id INT NULL,
    ADD COLUMN route_id INT NULL AFTER jeepney_id,
    ADD COLUMN status ENUM('In progress', 'Pending', 'Confirmed') NOT NULL DEFAULT 'Pending' AFTER recorded_at,
    ADD COLUMN starting_odometer DECIMAL(10,1) NULL AFTER status,
    ADD COLUMN boarded_count INT NOT NULL DEFAULT 0 AFTER starting_odometer,
    ADD COLUMN dropped_off_count INT NOT NULL DEFAULT 0 AFTER boarded_count,
    ADD COLUMN confirmation_notes TEXT NULL AFTER dropped_off_count,
    ADD KEY idx_trips_route_id (route_id),
    ADD KEY idx_trips_status (status),
    ADD CONSTRAINT fk_trips_route FOREIGN KEY (route_id)
        REFERENCES routes (id) ON UPDATE CASCADE ON DELETE SET NULL;

UPDATE trips t
INNER JOIN jeepney j ON j.id = t.jeepney_id
SET t.route_id = j.route_id,
    t.boarded_count = t.number_of_passengers;
