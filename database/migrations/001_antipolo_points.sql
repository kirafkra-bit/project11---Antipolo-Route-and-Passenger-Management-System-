-- Run this migration against the existing `polonav` database.
-- It is additive: existing routes and trips remain valid.

CREATE TABLE IF NOT EXISTS antipolo_points (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO antipolo_points (point_key, name, latitude, longitude)
VALUES
('simbahan', 'Antipolo Simbahan (Antipolo Cathedral)', 14.58750000, 121.17680000),
('robinsons_antipolo', 'Robinsons Antipolo', 14.58490000, 121.17620000),
('masinag', 'Masinag', 14.62470000, 121.12110000),
('sm_city_masinag', 'SM City Masinag', 14.62480000, 121.12140000),
('lrt_2_antipolo', 'LRT-2 Antipolo Station', 14.62510000, 121.12100000),
('cogeo', 'Cogeo', 14.61570000, 121.13500000),
('cogeo_gate_2', 'Cogeo Gate 2', 14.62500000, 121.14300000),
('padilla', 'Padilla', 14.61700000, 121.14700000),
('paenaan', 'Paenaan', 14.63200000, 121.15300000),
('antipolo_hills', 'Antipolo Hills', 14.63000000, 121.16000000),
('mayamot', 'Mayamot', 14.62600000, 121.10500000),
('francisville', 'Francisville', 14.65300000, 121.13000000),
('langhaya', 'Langhaya', 14.65000000, 121.16000000),
('sampaloc', 'Sampaloc', 14.62600000, 121.17600000),
('bayan', 'Bayan', 14.58700000, 121.17600000),
('san_jose', 'San Jose', 14.59000000, 121.17000000),
('mambugan', 'Mambugan', 14.63700000, 121.12000000),
('sumulong', 'Sumulong Circle', 14.60020000, 121.17500000),
('sumulong_highway', 'Sumulong Highway', 14.60000000, 121.17500000),
('cloud_9', 'Cloud 9', 14.59000000, 121.14000000),
('hinulugang_taktak', 'Hinulugang Taktak', 14.58450000, 121.17400000),
('pinto_art_museum', 'Pinto Art Museum', 14.57800000, 121.17600000),
('antipolo_junction', 'Antipolo Junction', 14.59900000, 121.17400000),
('cupang', 'Cupang', 14.60300000, 121.10000000),
('dalig', 'Dalig', 14.59000000, 121.14000000),
('delapaz', 'Dela Paz', 14.61000000, 121.10500000),
('inarawan', 'Inarawan', 14.55500000, 121.19000000),
('boso_boso', 'Boso-Boso', 14.61000000, 121.24000000),
('calawis', 'Calawis', 14.58000000, 121.25000000),
('pantay', 'Pantay', 14.63000000, 121.20000000),
('cubao', 'Cubao', 14.61900000, 121.05600000),
('marikina', 'Marikina', 14.65000000, 121.10200000),
('tanay', 'Tanay', 14.49700000, 121.28500000)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    latitude = VALUES(latitude),
    longitude = VALUES(longitude),
    is_active = 1;

ALTER TABLE routes
    ADD COLUMN pickup_point_id INT NULL,
    ADD COLUMN dropoff_point_id INT NULL,
    ADD KEY idx_routes_pickup_point (pickup_point_id),
    ADD KEY idx_routes_dropoff_point (dropoff_point_id),
    ADD CONSTRAINT fk_routes_pickup_point
        FOREIGN KEY (pickup_point_id) REFERENCES antipolo_points (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_routes_dropoff_point
        FOREIGN KEY (dropoff_point_id) REFERENCES antipolo_points (id)
        ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE trips
    ADD COLUMN pickup_point_id INT NULL,
    ADD COLUMN dropoff_point_id INT NULL,
    ADD KEY idx_trips_pickup_point (pickup_point_id),
    ADD KEY idx_trips_dropoff_point (dropoff_point_id),
    ADD CONSTRAINT fk_trips_pickup_point
        FOREIGN KEY (pickup_point_id) REFERENCES antipolo_points (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_trips_dropoff_point
        FOREIGN KEY (dropoff_point_id) REFERENCES antipolo_points (id)
        ON UPDATE CASCADE ON DELETE SET NULL;
