-- Use this migration if driver_documents_migration.sql was already run.
-- It adds only the newly requested driver documents.

USE polonav;

ALTER TABLE drivers
    ADD COLUMN or_cr_file VARCHAR(255) NULL AFTER valid_id_file,
    ADD COLUMN ctpl_file VARCHAR(255) NULL AFTER or_cr_file,
    ADD COLUMN mvir_file VARCHAR(255) NULL AFTER ctpl_file,
    ADD COLUMN emission_file VARCHAR(255) NULL AFTER mvir_file;

-- Run this only if passengers does not already have valid_id_file.
-- ALTER TABLE passengers
--     ADD COLUMN valid_id_file VARCHAR(255) NULL AFTER address;
