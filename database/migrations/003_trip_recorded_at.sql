-- Add an actual record date to trips without inventing dates for older records.

ALTER TABLE trips
    ADD COLUMN recorded_at TIMESTAMP NULL DEFAULT NULL AFTER drivers_id;
